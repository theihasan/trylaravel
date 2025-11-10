<template>
    <div
        class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-100/10 p-6"
    >
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 dark:text-zinc-300 text-lg">
                My Sources
            </h3>
            <Link
                :href="route('sources.index')"
                class="text-red-600 dark:text-red-500 hover:text-red-700 text-sm font-medium"
            >
                Manage
            </Link>
        </div>

        <div v-if="sources && sources.length > 0" class="space-y-3">
            <div
                v-for="source in sources.slice(0, 10)"
                :key="source.id"
                class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition-colors group"
            >
                <!-- Favicon -->
                <div class="flex-shrink-0">
                    <img
                        :src="
                            source.favicon_url || getDefaultFavicon(source.url)
                        "
                        :alt="source.name"
                        class="w-5 h-5 rounded"
                        @error="handleImageError"
                    />
                </div>

                <!-- Source Info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p
                            class="text-sm font-medium text-gray-900 dark:text-zinc-300 truncate"
                        >
                            {{ source.name }}
                        </p>
                        <span
                            class="text-xs text-gray-500 dark:text-zinc-400 ml-2"
                            >{{ source.posts_count || 0 }}</span
                        >
                    </div>
                    <p
                        class="text-xs text-gray-500 dark:text-zinc-500 truncate"
                    >
                        {{ getDomainFromUrl(source.url) }}
                    </p>
                </div>

                <!-- Status indicator -->
                <div class="flex-shrink-0">
                    <div
                        :class="
                            source.is_active ? 'bg-green-400' : 'bg-red-400'
                        "
                        class="w-2 h-2 rounded-full"
                        :title="source.is_active ? 'Active' : 'Inactive'"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="sources && sources.length === 0"
            class="text-center py-6"
        >
            <div
                class="w-12 h-12 mx-auto mb-3 bg-gray-100 dark:bg-zinc-600 text-gray-400 dark:text-zinc-400 rounded-full flex items-center justify-center"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    class="size-6"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12.75 19.5v-.75a7.5 7.5 0 0 0-7.5-7.5H4.5m0-6.75h.75c7.87 0 14.25 6.38 14.25 14.25v.75M6 18.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z"
                    />
                </svg>
            </div>
            <p class="text-sm text-gray-600 dark:text-zinc-600 mb-3">
                No sources yet
            </p>
            <button
                @click="$emit('add-source')"
                class="text-red-600 dark:text-red-500 hover:text-red-700 text-sm font-medium"
            >
                Add your first source
            </button>
        </div>

        <!-- Loading state -->
        <div v-else class="space-y-3">
            <div
                v-for="i in 5"
                :key="i"
                class="flex items-center space-x-3 p-2"
            >
                <div
                    class="w-5 h-5 bg-gray-200 dark:bg-zinc-600 rounded animate-pulse"
                ></div>
                <div class="flex-1">
                    <div
                        class="h-4 bg-gray-200 dark:bg-zinc-600 rounded w-24 mb-1 animate-pulse"
                    ></div>
                    <div
                        class="h-3 bg-gray-200 dark:bg-zinc-600 rounded w-16 animate-pulse"
                    ></div>
                </div>
                <div
                    class="w-2 h-2 bg-gray-200 dark:bg-zinc-600 rounded-full animate-pulse"
                ></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    sources: {
        type: Array,
        default: null,
    },
});

defineEmits(["add-source"]);

// Helper functions
const getDomainFromUrl = (url) => {
    try {
        const domain = new URL(url).hostname.replace("www.", "");
        return domain;
    } catch {
        return "Unknown";
    }
};

const getDefaultFavicon = (url) => {
    try {
        const domain = new URL(url).hostname;
        return `https://www.google.com/s2/favicons?domain=${domain}&sz=32`;
    } catch {
        return "https://via.placeholder.com/20/e5e7eb/9ca3af?text=?";
    }
};

const handleImageError = (event) => {
    event.target.src = "https://via.placeholder.com/20/e5e7eb/9ca3af?text=?";
};
</script>
