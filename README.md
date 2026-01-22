# ShadcThemes Tools

Powerful Artisan commands to seamlessly integrate and manage [tweakcn](https://tweakcn.com) / shadcn themes in your Laravel Inertia applications.

Support for **Inertia React** and **Inertia Vue**.

## Features

- 🎨 **Instant Theme Setup**: Scaffolds theme configuration, hooks, and UI components in seconds.
- 📥 **One-Command Import**: Import any theme from `tweakcn.com` directly via URL.
- 🌗 **Dark Mode Ready**: Automatically handles CSS variables for light and dark modes.
- 💅 **Tailwind CSS Integration**: Injects necessary shadow and font mappings into your `app.css`.
- ⚛️ **Multi-Stack Support**: First-class support for both React and Vue (Inertia).

## Requirements

- PHP 8.4+
- Laravel 12+
- Inertia.js (React or Vue)
- Tailwind CSS

## Installation

Install the package via Composer:

```bash
composer require yukazakiri/shadcthemes-tools
```

## Quick Start

### 1. Setup the Environment

Run the setup command to initialize the theming system in your application.

```bash
php artisan theme:setup
```

You will be presented with two options:

- **Starter Kit Template**: Installs a full suite of components (`ThemeSwitcher`, `useColorTheme`), configures `app.css`, and even sets up a "Personalization" section in your Profile page settings. Ideal for fresh projects or those following standard starter kit structures.
- **Stand-alone Configuration**: Installs only the core plumbing (CSS files, config, and state management hooks). Perfect if you want to build your own UI or integrate into an existing custom layout without overwriting files.

### 2. Build Assets

After setup, rebuild your frontend assets to compile the new CSS variables:

```bash
npm run dev
# or
npm run build
```

## Usage

### Importing Themes

Find a theme you like on [shadcn themes](https://shadcnthemer.com) or [tweakcn](https://tweakcn.com), copy its JSON URL, and run:

```bash
php artisan theme:add https://shadcnthemer.com/r/themes/your-theme-id.json
```

This command will:

1. Download the theme definition.
2. Generate a CSS file in `resources/css/themes/`.
3. Register the theme in `resources/js/conf/themes.ts`.
4. Add the necessary imports to `app.css`.

### Removing Themes

To remove a theme and clean up its files:

```bash
php artisan theme:remove theme-name
```

### Updating Components

If the package updates its UI stubs (like the Theme Switcher component), you can pull the latest versions into your app:

```bash
php artisan theme:update
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to set up a local development environment and contribute to this project.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
