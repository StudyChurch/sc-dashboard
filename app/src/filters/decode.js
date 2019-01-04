let he = require('he');

export default (value) => {
  if (value) {
    return he.decode(value);
  }
}