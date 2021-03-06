import fgInput from './components/Inputs/formGroupInput.vue';
import DropDown from './components/Dropdown.vue';
import Card from './components/Cards/Card.vue';
import Button from './components/Button.vue';
import { Input, InputNumber, Tooltip, Popover, Alert, Message } from 'element-ui';

// Fontawesome
import { library } from '@fortawesome/fontawesome-svg-core';
import {
  faBook,
  faUsers,
  faChevronRight,
  faComments,
  faList,
  faUser,
  faCogs,
  faBell,
  faEdit,
  faTrash,
  faEye,
  faEyeSlash,
  faChurch,
  faArchive,
  faTimes,
  faPlus,
  faBars,
  faDollarSign,
  faArrowsAlt,
  faPrint,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

library.add(
  faBook,
  faUsers,
  faChevronRight,
  faComments,
  faList,
  faUser,
  faCogs,
  faBell,
  faEdit,
  faTrash,
  faEye,
  faEyeSlash,
  faChurch,
  faArchive,
  faTimes,
  faPlus,
  faBars,
  faArrowsAlt,
  faDollarSign,
  faPrint
);

/**
 * You can register global components here and use them as a plugin in your main Vue instance
 */

const GlobalComponents = {
  install (Vue) {
    Vue.component('fg-input', fgInput);
    Vue.component('drop-down', DropDown);
    Vue.component('card', Card);
    Vue.component('n-button', Button);
    Vue.component('font-awesome-icon', FontAwesomeIcon);
    Vue.component(Input.name, Input);
    Vue.component(InputNumber.name, InputNumber);
    Vue.component(Alert.name, Alert);
    Vue.component(Message.name, Message);
    Vue.use(Tooltip);
    Vue.use(Popover);
  }
};

export default GlobalComponents;
