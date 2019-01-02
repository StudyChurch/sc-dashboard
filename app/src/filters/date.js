import moment from 'moment';

export default (value, format) => {
  if (value) {
    return moment(String(value)).fromNow();
  }
}