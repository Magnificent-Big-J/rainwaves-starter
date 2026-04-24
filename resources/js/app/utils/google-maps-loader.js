import { ref } from 'vue';

let googleMapsPromise;

export function useGoogleMapsLoader() {
    const isLoaded = ref(Boolean(window.google?.maps?.places));
    const loadError = ref(null);

    const load = async () => {
        if (window.google?.maps?.places) {
            isLoaded.value = true;

            return window.google;
        }

        if (!googleMapsPromise) {
            const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '';

            if (!apiKey) {
                const error = new Error('Missing VITE_GOOGLE_MAPS_API_KEY');
                loadError.value = error;
                throw error;
            }

            googleMapsPromise = new Promise((resolve, reject) => {
                const existing = document.querySelector('script[data-google-maps-loader="true"]');

                if (existing) {
                    if (window.google?.maps?.places) {
                        resolve(window.google);
                        return;
                    }

                    existing.addEventListener('load', () => resolve(window.google));
                    existing.addEventListener('error', () => reject(new Error('Failed to load Google Maps script')));
                    return;
                }

                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places`;
                script.async = true;
                script.defer = true;
                script.dataset.googleMapsLoader = 'true';
                script.onload = () => resolve(window.google);
                script.onerror = () => reject(new Error('Failed to load Google Maps script'));
                document.head.append(script);
            });
        }

        try {
            await googleMapsPromise;
            isLoaded.value = true;

            return window.google;
        } catch (error) {
            loadError.value = error;
            throw error;
        }
    };

    return {
        isLoaded,
        loadError,
        load,
    };
}
