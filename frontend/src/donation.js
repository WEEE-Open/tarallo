import { createApp } from 'vue';

import App from './views/DonationView.vue';

function initDonation(data) {
    const app = createApp(App, {donation: data});
    app.mount('#app');
}

window.initDonation = initDonation;