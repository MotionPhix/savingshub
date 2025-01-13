import '../css/app.css';
import './bootstrap';

import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {createApp, h} from 'vue';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import VueApexCharts from "vue3-apexcharts";
import { Modal, ModalLink, putConfig, renderApp } from '@inertiaui/modal-vue'
import { createPinia } from 'pinia'
import { variantJS } from '@variantjs/vue'

const appName = import.meta.env.VITE_APP_NAME || 'SavingsHub';
const pinia = createPinia()

putConfig({
  type: 'modal',
  navigate: false,
  modal: {
    closeButton: true,
    closeExplicitly: false,
    maxWidth: 'md',
    paddingClasses: 'p-4 sm:p-5',
    panelClasses: 'bg-white rounded-xl shadow dark:bg-gray-800',
    position: 'center',
  },
  slideover: {
    closeButton: true,
    closeExplicitly: false,
    maxWidth: 'md',
    paddingClasses: 'p-4 sm:p-5',
    panelClasses: 'bg-white shadow dark:bg-gray-800',
    position: 'right',
  },
})

const configuration = {
  //...
}

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob('./Pages/**/*.vue'),
    ),
  setup({el, App, props, plugin}) {
    return createApp({ render: renderApp(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(VueApexCharts)
      .use(pinia)
      .use(variantJS, configuration)
      .component('GlobalModal', Modal)
      .component('ModalLink', ModalLink)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
