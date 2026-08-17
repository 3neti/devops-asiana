<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import { dashboard } from '@/routes';

defineProps<{
    document: {
        key: string;
        title: string;
        source_path: string;
        html: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Firm Console',
                href: dashboard(),
            },
            {
                title: 'Institutional documents',
                href: '#',
            },
        ],
    },
});
</script>

<template>
    <Head :title="document.title" />

    <div
        class="min-h-full bg-stone-50/70 px-4 py-6 sm:px-6 lg:px-8 dark:bg-slate-950"
    >
        <div class="mx-auto max-w-5xl">
            <header
                class="mb-5 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        <FileText class="size-5" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-teal-700 uppercase"
                        >
                            Canonical repository document
                        </p>
                        <p class="mt-1 font-mono text-xs text-slate-500">
                            {{ document.source_path }}
                        </p>
                    </div>
                </div>
                <span
                    class="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-500 dark:border-slate-700"
                >
                    Read-only
                </span>
            </header>

            <article
                class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-[15px] leading-7 text-slate-700 shadow-sm sm:px-10 lg:px-14 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 [&_a]:font-medium [&_a]:text-teal-700 [&_a]:underline [&_blockquote]:my-6 [&_blockquote]:border-l-4 [&_blockquote]:border-teal-600 [&_blockquote]:pl-5 [&_code]:rounded [&_code]:bg-slate-100 [&_code]:px-1.5 [&_code]:py-0.5 dark:[&_code]:bg-slate-800 [&_h1]:font-serif [&_h1]:text-4xl [&_h1]:leading-tight [&_h1]:font-semibold [&_h1]:tracking-tight [&_h1]:text-slate-950 dark:[&_h1]:text-stone-50 [&_h2]:mt-10 [&_h2]:font-serif [&_h2]:text-2xl [&_h2]:font-semibold [&_h2]:text-slate-950 dark:[&_h2]:text-stone-50 [&_h3]:mt-7 [&_h3]:text-lg [&_h3]:font-semibold [&_li]:my-1 [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:my-4 [&_pre]:my-5 [&_pre]:overflow-x-auto [&_pre]:rounded-xl [&_pre]:bg-slate-950 [&_pre]:p-5 [&_pre]:text-slate-100 [&_strong]:font-semibold [&_strong]:text-slate-950 dark:[&_strong]:text-stone-50 [&_table]:my-6 [&_table]:w-full [&_table]:border-collapse [&_td]:border-b [&_td]:border-slate-200 [&_td]:p-3 [&_td]:align-top dark:[&_td]:border-slate-800 [&_th]:border-b [&_th]:border-slate-300 [&_th]:p-3 [&_th]:text-left dark:[&_th]:border-slate-700 [&_ul]:my-4 [&_ul]:list-disc [&_ul]:pl-6"
                v-html="document.html"
            />
        </div>
    </div>
</template>
