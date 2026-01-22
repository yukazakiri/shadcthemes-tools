<script setup lang="ts">
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import SettingsLayout from "@/layouts/settings/Layout.vue";
import DeleteUser from "@/components/DeleteUser.vue";
import Heading from "@/components/Heading.vue";
import InputError from "@/components/InputError.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import ThemeSwitcher from "@/components/ThemeSwitcher.vue";
import { type SharedData } from "@/types";
import { edit, update } from "@/routes/profile";
import { send } from "@/routes/verification";

defineProps<{
  mustVerifyEmail: boolean;
  status?: string;
}>();

const page = usePage<SharedData>();
const user = page.props.auth.user;

const form = useForm({
  name: user.name,
  email: user.email,
});

const submit = () => {
  form.patch(update().url, {
    preserveScroll: true,
  });
};

const breadcrumbs = [{ title: "Profile settings", href: edit().url }];
</script>

<template>
  <AppLayout title="Profile settings" :breadcrumbs="breadcrumbs">
    <Head title="Profile settings" />

    <SettingsLayout>
      <div class="space-y-6">
        <Heading
          title="Profile information"
          description="Update your name and email address"
        />

        <form @submit.prevent="submit" class="space-y-6">
          <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
              id="name"
              v-model="form.name"
              required
              autocomplete="name"
              placeholder="Full name"
            />
            <InputError :message="form.errors.name" />
          </div>

          <div class="grid gap-2">
            <Label for="email">Email address</Label>
            <Input
              id="email"
              type="email"
              v-model="form.email"
              required
              autocomplete="username"
              placeholder="Email address"
            />
            <InputError :message="form.errors.email" />
          </div>

          <div v-if="mustVerifyEmail && user.email_verified_at === null">
            <p class="-mt-4 text-sm text-muted-foreground">
              Your email address is unverified.
              <Link
                :href="send().url"
                method="post"
                as="button"
                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
              >
                Click here to resend the verification email.
              </Link>
            </p>

            <div
              v-if="status === 'verification-link-sent'"
              class="mt-2 text-sm font-medium text-green-600"
            >
              A new verification link has been sent to your email address.
            </div>
          </div>

          <div class="flex items-center gap-4">
            <Button :disabled="form.processing">Save</Button>

            <Transition
              enter-active-class="transition ease-in-out"
              enter-from-class="opacity-0"
              leave-active-class="transition ease-in-out"
              leave-to-class="opacity-0"
            >
              <p
                v-if="form.recentlySuccessful"
                class="text-sm text-neutral-600"
              >
                Saved
              </p>
            </Transition>
          </div>
        </form>
      </div>

      <div class="space-y-6">
        <Heading
          title="Personalization"
          description="Pick a theme that matches your style"
        />
        <ThemeSwitcher />
      </div>

      <DeleteUser />
    </SettingsLayout>
  </AppLayout>
</template>
