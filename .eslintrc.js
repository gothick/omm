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
    "no-underscore-dangle": 0,
    // All the symfony/ux-chartjs examples do this all over the place,
    // and I don't care much one way or the other.
    "no-param-reassign": 0,
  },
};
