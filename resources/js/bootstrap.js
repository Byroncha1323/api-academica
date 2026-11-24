// Expone Axios con el encabezado de solicitudes AJAX. Esta configuración no asigna tokens ni autoriza operaciones.
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
