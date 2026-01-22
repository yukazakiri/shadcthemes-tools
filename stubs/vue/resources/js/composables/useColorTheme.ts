import { themes, type ColorTheme } from "@/conf/themes";
import { ref } from "vue";

const currentTheme = ref<ColorTheme>("default");
const themeClassPrefix = "theme-";

const isValidTheme = (theme: string | null): theme is ColorTheme => {
  if (!theme) {
    return false;
  }

  return themes.some((config) => config.id === theme);
};

const setCookie = (name: string, value: string, days = 365): void => {
  if (typeof document === "undefined") {
    return;
  }

  const maxAge = days * 24 * 60 * 60;
  document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getStoredTheme = (): ColorTheme => {
  if (typeof window === "undefined") {
    return "default";
  }

  const storedTheme = localStorage.getItem("color-theme");

  if (isValidTheme(storedTheme)) {
    return storedTheme;
  }

  return "default";
};

const applyTheme = (theme: ColorTheme): void => {
  if (typeof document === "undefined") {
    return;
  }

  const root = document.documentElement;
  const themeClasses = Array.from(root.classList).filter((className) =>
    className.startsWith(themeClassPrefix),
  );

  themeClasses.forEach((className) => root.classList.remove(className));

  if (theme !== "default") {
    root.classList.add(`${themeClassPrefix}${theme}`);
  }
};

export function initializeColorTheme(): void {
  if (typeof window === "undefined") {
    return;
  }

  if (!localStorage.getItem("color-theme")) {
    localStorage.setItem("color-theme", "default");
    setCookie("color-theme", "default");
  }

  currentTheme.value = getStoredTheme();
  applyTheme(currentTheme.value);
}

export function useColorTheme() {
  const updateColorTheme = (theme: ColorTheme): void => {
    currentTheme.value = theme;
    localStorage.setItem("color-theme", theme);
    setCookie("color-theme", theme);
    applyTheme(theme);
  };

  return {
    colorTheme: currentTheme,
    updateColorTheme,
  };
}
