module.exports = {
  env: {
    browser: true,
    es2021: true,
  },
  extends: [
    'airbnb-base',
  ],
  parserOptions: {
    ecmaVersion: 13,
    sourceType: 'module',
  },
  rules: {
    // Overriding AirBNB rules:
    // The Stimulus convention seems to be dangling underscores, and I'm
    // okay with that.
    "no-underscore-dangle": 0
  },
};
