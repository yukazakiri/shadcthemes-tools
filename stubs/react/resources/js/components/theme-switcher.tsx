import { themes, type ColorTheme } from "@/conf/themes";
import { useColorTheme } from "@/hooks/use-color-theme";
import { cn } from "@/lib/utils";
import { Check } from "lucide-react";

export default function ThemeSwitcher() {
  const { colorTheme, updateColorTheme } = useColorTheme();

  return (
    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      {themes.map((theme) => {
        const isActive = colorTheme === theme.id;

        return (
          <button
            key={theme.id}
            type="button"
            onClick={() => updateColorTheme(theme.id as ColorTheme)}
            className={cn(
              "group relative flex flex-col overflow-hidden rounded-xl border text-left transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2",
              isActive
                ? "border-primary ring-1 ring-primary shadow-md"
                : "border-border hover:border-primary/50 hover:shadow-lg",
            )}
          >
            <div className="relative h-24 w-full overflow-hidden bg-muted/30">
              <div className="absolute inset-0 p-4">
                <div className="flex h-full flex-col gap-2">
                  <div
                    className="h-2 w-1/3 rounded-full opacity-60"
                    style={{ backgroundColor: theme.colors.primary }}
                  />

                  <div className="flex gap-2">
                    <div
                      className="h-8 w-full rounded-md border bg-background shadow-sm"
                      style={{ borderColor: theme.colors.secondary }}
                    />
                    <div
                      className="h-8 w-1/3 rounded-md opacity-80"
                      style={{ backgroundColor: theme.colors.primary }}
                    />
                  </div>
                </div>
              </div>
            </div>

            <div className="flex flex-1 flex-col gap-2 p-4">
              <div className="flex items-center justify-between">
                <span
                  className="font-semibold"
                  style={{ fontFamily: theme.font }}
                >
                  {theme.name}
                </span>
                {isActive && <Check className="h-4 w-4 text-primary" />}
              </div>

              <p className="text-xs text-muted-foreground line-clamp-2">
                {theme.description}
              </p>

              <div className="mt-auto flex items-center justify-between gap-2 pt-2">
                <div className="flex gap-1.5">
                  <div
                    className="h-4 w-4 rounded-full border border-border"
                    style={{ background: theme.colors.primary }}
                    title="Primary"
                  />
                  <div
                    className="h-4 w-4 rounded-full border border-border"
                    style={{ background: theme.colors.secondary }}
                    title="Secondary"
                  />
                  <div
                    className="h-4 w-4 rounded-full border border-border"
                    style={{ background: theme.colors.accent }}
                    title="Accent"
                  />
                </div>
                <span
                  className="text-xs text-muted-foreground truncate"
                  style={{ fontFamily: theme.font }}
                >
                  {theme.font}
                </span>
              </div>
            </div>
          </button>
        );
      })}
    </div>
  );
}
