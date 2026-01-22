<script setup lang="ts">
import { themes, type ColorTheme } from "@/conf/themes";
import { useColorTheme } from "@/composables/useColorTheme";
import { Check } from "lucide-vue-next";
import { cn } from "@/lib/utils";

const { colorTheme, updateColorTheme } = useColorTheme();
</script>

<template>
  <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
    <button
      v-for="theme in themes"
      :key="theme.id"
      type="button"
      @click="updateColorTheme(theme.id as ColorTheme)"
      :class="
        cn(
          'group relative flex flex-col overflow-hidden rounded-xl border text-left transition-all duration-300 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2',
          colorTheme === theme.id
            ? 'border-primary ring-1 ring-primary shadow-md'
            : 'border-border hover:border-primary/50 hover:shadow-lg',
        )
      "
    >
      <div class="relative h-24 w-full overflow-hidden bg-muted/30">
        <div class="absolute inset-0 p-4">
          <div class="flex h-full flex-col gap-2">
            <div
              class="h-2 w-1/3 rounded-full opacity-60"
              :style="{ backgroundColor: theme.colors.primary }"
            />
            <div class="flex gap-2">
              <div
                class="h-8 w-full rounded-md border bg-background shadow-sm"
                :style="{ borderColor: theme.colors.secondary }"
              />
              <div
                class="h-8 w-1/3 rounded-md opacity-80"
                :style="{ backgroundColor: theme.colors.primary }"
              />
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-1 flex-col gap-2 p-4">
        <div class="flex items-center justify-between">
          <span class="font-semibold" :style="{ fontFamily: theme.font }">
            {{ theme.name }}
          </span>
          <Check v-if="colorTheme === theme.id" class="h-4 w-4 text-primary" />
        </div>

        <p class="text-xs text-muted-foreground line-clamp-2">
          {{ theme.description }}
        </p>

        <div class="mt-auto flex items-center justify-between gap-2 pt-2">
          <div class="flex gap-1.5">
            <div
              class="h-4 w-4 rounded-full border border-border"
              :style="{ background: theme.colors.primary }"
              title="Primary"
            />
            <div
              class="h-4 w-4 rounded-full border border-border"
              :style="{ background: theme.colors.secondary }"
              title="Secondary"
            />
            <div
              class="h-4 w-4 rounded-full border border-border"
              :style="{ background: theme.colors.accent }"
              title="Accent"
            />
          </div>
          <span
            class="text-xs text-muted-foreground truncate"
            :style="{ fontFamily: theme.font }"
          >
            {{ theme.font }}
          </span>
        </div>
      </div>
    </button>
  </div>
</template>
