<template>
    <div ref="rootRef" class="app-address-autocomplete">
        <AppTextField
            v-model="query"
            :label="label"
            :placeholder="placeholder"
            :hint="resolvedHint"
            :prepend-inner-icon="prependInnerIcon"
            :append-inner-icon="query ? 'mdi-close-circle-outline' : null"
            :loading="isLoading"
            :error="Boolean(loadError)"
            :error-messages="resolvedErrors"
            :hide-details="hideDetails"
            @focus="onFocus"
            @keydown="onKeydown"
            @update:model-value="onInput"
            @click:append-inner="clearInput"
        />

        <v-card
            v-if="showSuggestions && suggestions.length"
            class="app-address-autocomplete__menu"
            elevation="8"
        >
            <v-list density="compact" class="app-address-autocomplete__list">
                <v-list-item
                    v-for="(suggestion, index) in suggestions"
                    :key="suggestion.place_id"
                    :active="index === highlightedIndex"
                    @mouseenter="highlightedIndex = index"
                    @click="selectSuggestion(suggestion)"
                >
                    <template #prepend>
                        <v-icon size="18" color="var(--rw-600)">mdi-map-marker-outline</v-icon>
                    </template>

                    <v-list-item-title>{{ suggestion.structured_formatting?.main_text || suggestion.description }}</v-list-item-title>
                    <v-list-item-subtitle>
                        {{ suggestion.structured_formatting?.secondary_text || suggestion.description }}
                    </v-list-item-subtitle>
                </v-list-item>
            </v-list>
        </v-card>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import { useGoogleMapsLoader } from '../utils/google-maps-loader';

const props = defineProps({
    modelValue: { type: Object, default: null },
    queryValue: { type: String, default: '' },
    label: { type: String, default: 'Address' },
    placeholder: { type: String, default: 'Type an address...' },
    hint: { type: String, default: '' },
    country: { type: String, default: '' },
    hideDetails: { type: [Boolean, String], default: 'auto' },
    prependInnerIcon: { type: String, default: 'mdi-map-marker-outline' },
    minChars: { type: Number, default: 3 },
    debounceMs: { type: Number, default: 250 },
});

const emit = defineEmits(['update:modelValue', 'update:queryValue', 'select', 'clear']);

const { isLoaded, loadError, load } = useGoogleMapsLoader();

const rootRef = ref(null);
const query = ref(props.queryValue || props.modelValue?.formatted_address || '');
const suggestions = ref([]);
const showSuggestions = ref(false);
const isLoading = ref(false);
const highlightedIndex = ref(-1);

let autocompleteService = null;
let placesService = null;
let fetchTimer = null;

const resolvedHint = computed(() => {
    if (props.hint) {
        return props.hint;
    }

    return 'Search for a full street address, suburb, city, or place.';
});

const resolvedErrors = computed(() => {
    if (!loadError.value) {
        return [];
    }

    return ['Unable to load Google Maps Places API. Add VITE_GOOGLE_MAPS_API_KEY to enable address search.'];
});

const initializeGoogleServices = async () => {
    const googleRef = await load();

    autocompleteService = new googleRef.maps.places.AutocompleteService();
    placesService = new googleRef.maps.places.PlacesService(document.createElement('div'));
};

const ensureServices = async () => {
    if (!autocompleteService || !placesService) {
        await initializeGoogleServices();
    }
};

const mapAddressComponents = (components) => {
    const result = {};

    for (const component of components) {
        for (const type of component.types) {
            result[type] = component.long_name;
        }
    }

    return result;
};

const getComponent = (components, type) => {
    const found = components.find((component) => component.types.includes(type));

    return found ? found.long_name : '';
};

const composeLineOne = (components) => {
    const streetNumber = getComponent(components, 'street_number');
    const route = getComponent(components, 'route');

    return [streetNumber, route].filter(Boolean).join(' ').trim();
};

const normalizePlace = (place) => {
    const addressComponents = place.address_components || [];
    const suburb = getComponent(addressComponents, 'sublocality_level_1')
        || getComponent(addressComponents, 'sublocality')
        || getComponent(addressComponents, 'neighborhood');

    return {
        google_place_id: place.place_id,
        formatted_address: place.formatted_address,
        address_line_1: composeLineOne(addressComponents),
        suburb,
        city: getComponent(addressComponents, 'locality')
            || getComponent(addressComponents, 'administrative_area_level_2'),
        state: getComponent(addressComponents, 'administrative_area_level_1'),
        country: getComponent(addressComponents, 'country'),
        postal_code: getComponent(addressComponents, 'postal_code'),
        latitude: place.geometry?.location?.lat() ?? null,
        longitude: place.geometry?.location?.lng() ?? null,
        address_components: mapAddressComponents(addressComponents),
    };
};

const resetSuggestions = () => {
    suggestions.value = [];
    showSuggestions.value = false;
    highlightedIndex.value = -1;
};

const fetchSuggestions = async (input) => {
    if (!input || input.length < props.minChars) {
        resetSuggestions();
        return;
    }

    await ensureServices();
    isLoading.value = true;

    const request = {
        input,
        types: ['geocode'],
    };

    if (props.country) {
        request.componentRestrictions = { country: props.country };
    }

    autocompleteService.getPlacePredictions(request, (predictions) => {
        suggestions.value = predictions || [];
        isLoading.value = false;
        showSuggestions.value = suggestions.value.length > 0;
        highlightedIndex.value = suggestions.value.length ? 0 : -1;
    });
};

const selectSuggestion = async (suggestion) => {
    if (!suggestion) {
        return;
    }

    query.value = suggestion.description;
    emit('update:queryValue', query.value);
    showSuggestions.value = false;

    await ensureServices();

    placesService.getDetails({
        placeId: suggestion.place_id,
        fields: ['place_id', 'formatted_address', 'address_components', 'geometry'],
    }, (place, status) => {
        if (status !== window.google.maps.places.PlacesServiceStatus.OK || !place) {
            return;
        }

        const payload = normalizePlace(place);
        emit('update:modelValue', payload);
        emit('select', payload);
    });
};

const clearInput = () => {
    if (!query.value && !props.modelValue) {
        return;
    }

    query.value = '';
    emit('update:queryValue', '');
    emit('update:modelValue', null);
    emit('clear');
    resetSuggestions();
};

const onInput = (value) => {
    emit('update:queryValue', value);
    emit('update:modelValue', null);

    if (fetchTimer) {
        clearTimeout(fetchTimer);
    }

    fetchTimer = setTimeout(() => {
        fetchSuggestions(value);
    }, props.debounceMs);
};

const onFocus = async () => {
    if (!isLoaded.value && !loadError.value) {
        await ensureServices();
    }

    if (suggestions.value.length && !props.modelValue) {
        showSuggestions.value = true;
    }
};

const onKeydown = (event) => {
    if (!showSuggestions.value || !suggestions.value.length) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedIndex.value = (highlightedIndex.value + 1) % suggestions.value.length;
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedIndex.value = (highlightedIndex.value - 1 + suggestions.value.length) % suggestions.value.length;
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        selectSuggestion(suggestions.value[highlightedIndex.value] || suggestions.value[0]);
        return;
    }

    if (event.key === 'Escape') {
        showSuggestions.value = false;
    }
};

const handleOutsideClick = (event) => {
    if (!rootRef.value?.contains(event.target)) {
        showSuggestions.value = false;
    }
};

watch(
    () => props.queryValue,
    (value) => {
        const nextValue = value || props.modelValue?.formatted_address || '';

        if (nextValue !== query.value) {
            query.value = nextValue;
        }
    },
);

watch(
    () => props.modelValue,
    (value) => {
        if (!value?.formatted_address && !props.queryValue) {
            return;
        }

        const nextValue = props.queryValue || value?.formatted_address || '';

        if (nextValue !== query.value) {
            query.value = nextValue;
        }
    },
    { deep: true },
);

onMounted(() => {
    document.addEventListener('click', handleOutsideClick);
});

onBeforeUnmount(() => {
    if (fetchTimer) {
        clearTimeout(fetchTimer);
    }

    document.removeEventListener('click', handleOutsideClick);
});
</script>

<style scoped>
.app-address-autocomplete {
    position: relative;
}

.app-address-autocomplete__menu {
    position: absolute;
    z-index: 30;
    top: calc(100% - 8px);
    left: 0;
    right: 0;
    border: 1px solid rgba(27, 39, 63, 0.12);
    background: #fff;
    box-shadow: 0 14px 30px rgba(9, 19, 37, 0.16);
}

.app-address-autocomplete__list {
    max-height: 280px;
    overflow-y: auto;
}
</style>
