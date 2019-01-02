import DateFilter from './filters/date'

const Filters = {
  install (Vue) {
    Vue.filter('dateFormat', DateFilter);
  }
};

export default Filters;
