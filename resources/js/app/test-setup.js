// happy-dom (and jsdom) don't implement visualViewport, which Vuetify's VOverlay
// positioning logic reads when a menu/dialog actually opens. Without this, any test
// that opens a real Vuetify overlay (v-menu, v-dialog, ...) throws
// "visualViewport is not defined" — a test-environment gap, not a real browser issue.
if (typeof window !== 'undefined' && !window.visualViewport) {
    window.visualViewport = {
        width: window.innerWidth,
        height: window.innerHeight,
        addEventListener: () => {},
        removeEventListener: () => {},
    };
}
