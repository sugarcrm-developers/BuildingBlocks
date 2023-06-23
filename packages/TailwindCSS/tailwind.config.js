/** @type {import('tailwindcss').Config} */

module.exports = {
  /**
   * Add more paths to this property to expand file coverage for where Tailwind classes can be used.
   */
  content: [
      'Files/custom/**/**/*.{js,less,php}',
  ],
  theme: {
    extend: {},
  },
  plugins: [],
  /**
   * NOTE: Due to compatibility with the less/php library, Sugar's customizations cannot support the opacity utility
   * classes. less/php does not yet support for the comma-less syntax for rgb() and rgba() mixins.
   * Please use the opacity property directly.
   */
  corePlugins: {
    preflight: false, // Already handling preflight styles in primary application
    backdropOpacity: false,
    backgroundOpacity: false,
    borderOpacity: false,
    divideOpacity: false,
    ringOpacity: false,
    textOpacity: false,
  }
}

