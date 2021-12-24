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
    // Can override the AirBNB rules here, e.g.:
    //    "no-restricted-syntax": 0,
  },
};
