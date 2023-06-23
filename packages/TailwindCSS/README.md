# TailwindCSS customization example

#### Table of Contents

- Getting started
- [What is TailwindCSS?](#what-is-tailwindcss)
- [How to use TailwindCSS?](#how-to-use-tailwindcss)
- [Installation for this module loadable package](#installation)

## Getting started
To prepare this package for testing the output files, run `yarn dev:build` within this folder. This will generate an 
unminified `tailwind.css` file. If you'd like to prepare this package for use in your Sugar instance, run `yarn build` 
to generate a production-ready, minified `tailwind.css` file. 

## What is TailwindCSS?
[TailwindCSS](https://tailwindcss.com) is a utility-first CSS framework that provides a flexible set of utilities for 
building any design, easily. It comes out of the box with tree shaking to reduce the build size.

## How to use TailwindCSS?
This framework includes utility CSS classes for almost any property you can think of. They have built on a shorthand 
syntax that keeps composition clean, and maintainable. For example, if you'd like to use a class in a standard 
JavaScript component or Handlebars template, it would like this:

```html
<div class="flex items-center">
    <button class="h-10 px-6 font-semibold rounded-md border border-slate-200 text-slate-900" type="button">
        Hello!
    </button>
</div>
```

or directly in a LESS file, as a mixin, using the `@apply` keyword:

```less
.test-selector {
  @apply flex;    // display: flex;
  @apply "mt-4";  // margin-top: 1rem;
}
```

This would result in a `tailwind.css` file containing all the class definitions from the above snippets. Unminified, it 
would it look like the following:

```css
.flex {
    display: flex;
}

.item-center {
    align-items: center;
}

.mt-4 {
    margin-top: 1rem;
}

/* ... etc*/
```

## Installation
First, you'll want to create a zip of all the files required for the MLP. It is easiest to include only files pertaining 
to the uploaded package. From within this folder, you can run:

```shell
$  yarn package
```

or if you'd like to run the zip command directly:

```shell
$  zip tailwindcss-mlp-example.zip manifest.php -r Files/
```

This will generate the file `tailwindcss-mlp-example.zip` that you can then upload to your instance using Module Loader. 

_Note_: If you're running MacOS, make sure that there are no `.DS_Store` files present in any of the folders and 
subfolders before creating the zip file, as this will cause issues when trying to upload the MLP.