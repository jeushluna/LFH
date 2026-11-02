if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => {
        navigator.serviceWorker.register("/lorie-food-hub-pos/service-worker.js")
            .then(registration => {
                console.log(
                    "Service Worker registered successfully:",
                    registration.scope
                );
            })
            .catch(error => {
                console.error(
                    "Service Worker registration failed:",
                    error
                );
            });
    });
}