<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUpRight, Scale } from '@lucide/vue';
import { dashboard, login, register } from '@/routes';
</script>

<template>
    <Head title="DevOps Asiana" />
    <div
        class="flex min-h-screen flex-col bg-stone-50 text-slate-950 dark:bg-slate-950 dark:text-stone-50"
    >
        <header
            class="flex items-center justify-between border-b border-slate-200 px-6 py-5 sm:px-10 dark:border-slate-800"
        >
            <div class="flex items-center gap-3 font-semibold">
                <Scale class="size-5 text-teal-700 dark:text-teal-400" />
                DevOps Asiana
            </div>
            <span class="text-xs text-slate-500">Firm Console</span>
        </header>
        <main
            class="mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center gap-8 px-6 py-20 sm:px-10"
        >
            <div class="space-y-4">
                <p
                    class="text-xs font-semibold tracking-widest text-teal-700 uppercase dark:text-teal-400"
                >
                    Partnership · Governance · Operations
                </p>
                <h1 class="text-4xl font-semibold tracking-tight sm:text-6xl">
                    DevOps Asiana
                </h1>
                <p class="text-lg text-slate-500 dark:text-slate-400">
                    Firm Console
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-flex items-center gap-3 rounded-lg bg-slate-950 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800 dark:bg-stone-100 dark:text-slate-950 dark:hover:bg-white"
                >
                    Open Console <ArrowUpRight class="size-4" />
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-flex items-center gap-3 rounded-lg bg-slate-950 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800 dark:bg-stone-100 dark:text-slate-950 dark:hover:bg-white"
                    >
                        Sign in <ArrowUpRight class="size-4" />
                    </Link>
                    <Link
                        :href="register()"
                        class="rounded-lg px-5 py-3 text-sm font-medium text-slate-600 hover:text-slate-950 dark:text-slate-400 dark:hover:text-white"
                        >Create account</Link
                    >
                </template>
            </div>
        </main>
        <footer
            class="border-t border-slate-200 px-6 py-5 text-xs text-slate-500 sm:px-10 dark:border-slate-800"
        >
            DevOps Asiana · Philippines
        </footer>
    </div>
</template>
