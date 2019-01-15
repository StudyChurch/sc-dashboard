import moment from 'moment';

export default (value, format) => {
  if (value) {
    return moment.utc(String(value)).local().fromNow();
  }
}