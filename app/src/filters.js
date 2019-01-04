import DateFilter from './filters/date'
import Decode from './filters/decode'

const Filters = {
  install (Vue) {
    Vue.filter('dateFormat', DateFilter);
    Vue.filter('decode', Decode);
  }
};

export default Filters;
