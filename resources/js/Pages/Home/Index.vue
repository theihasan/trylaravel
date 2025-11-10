<script setup>
import { Head, Link, usePage, InfiniteScroll, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import GuestPromptModal from "@/Components/GuestPromptModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import { useToast } from "@/composables/useToast";
import ToastContainer from "@/Components/ToastContainer.vue";
import SourcesWidget from "@/Components/SourcesWidget.vue";

const props = defineProps({
    posts: Object,
    stats: Object,
    filters: Object,
    userSources: Array,
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAuthenticated = computed(() => !!user.value);

const { success, error } = useToast();

// Watch for flash messages and show toasts
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            success(flash.success);
        }
        if (flash?.error) {
            error(flash.error);
        }
    },
    { immediate: true }
);

// Add Source Modal
const showAddSourceModal = ref(false);

const openAddSourceModal = () => {
    showAddSourceModal.value = true;
};

const closeAddSourceModal = () => {
    showAddSourceModal.value = false;
    addSourceForm.reset();
};

// Guest Prompt Modal
const showGuestModal = ref(false);

const showGuestPrompt = () => {
    showGuestModal.value = true;
};

const closeGuestModal = () => {
    showGuestModal.value = false;
};

const addSourceForm = useForm({
    url: "",
});

const submitAddSource = () => {
    addSourceForm.post(route("sources.store"), {
        onSuccess: () => {
            closeAddSourceModal();
            success("Source added successfully!");
        },
        onError: () => {
            error("Failed to add source. Please try again.");
        },
    });
};

// Post interactions
const toggleLike = async (post) => {
    if (!isAuthenticated.value) return;

    try {
        const response = await fetch(route("posts.like", post.slug), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content,
                Accept: "application/json",
            },
        });

        const data = await response.json();

        // Update post data reactively
        post.is_liked = data.is_liked;
        post.likes_count = data.likes_count;
    } catch (err) {
        error("Failed to like post.");
    }
};

const toggleBookmark = async (post) => {
    if (!isAuthenticated.value) return;

    try {
        const response = await fetch(route("posts.bookmark", post.slug), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content,
                Accept: "application/json",
            },
        });

        const data = await response.json();

        // Update post data reactively
        post.is_bookmarked = data.is_bookmarked;
        success(data.is_bookmarked ? "Post bookmarked!" : "Bookmark removed!");
    } catch (err) {
        error("Failed to bookmark post.");
    }
};

const markAsSeen = async (post) => {
    if (!isAuthenticated.value || post.is_seen) return;

    try {
        const response = await fetch(route("posts.mark-seen", post.slug), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content,
                Accept: "application/json",
            },
        });

        const data = await response.json();

        // Update post data reactively
        post.is_seen = data.is_seen;
        success("Post marked as seen!");
    } catch (err) {
        error("Failed to mark post as seen.");
    }
};

const markAsUnseen = async (post) => {
    if (!isAuthenticated.value || !post.is_seen) return;

    try {
        const response = await fetch(route("posts.mark-unseen", post.slug), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                )?.content,
                Accept: "application/json",
            },
        });

        const data = await response.json();

        // Update post data reactively
        post.is_seen = data.is_seen;
        success("Post marked as unseen!");
    } catch (err) {
        error("Failed to mark post as unseen.");
    }
};

const getPostTypeIcon = (type) => {
    // This function is used for reference but icons are inline in template
    return type;
};

const getPostAction = (post) => {
    if (post.type.value === "video") return "Watch →";
    if (post.type.value === "podcast") return "Listen →";
    return "Read →";
};

const getPostTypeColors = (type) => {
    const colors = {
        blue: "bg-blue-100 text-blue-700",
        red: "bg-red-100 text-red-700",
        purple: "bg-purple-100 text-purple-700",
    };
    return colors[type] || colors.blue;
};
</script>

<template>
    <Head title="LaravelSense - Your Laravel Community Feed" />

    <div
        class="antialiased bg-gray-50 dark:bg-transparent font-sans min-h-screen relative w-full"
    >
        <div
            class="absolute inset-0 -z-10 dark:bg-zinc-950 [background-image:radial-gradient(ellipse_80%_60%_at_50%_0%,rgba(239,68,68,0.25),transparent_70%)]"
        ></div>

        <!-- Header -->
        <header
            class="sticky top-0 z-50 bg-white/95 dark:bg-zinc-900/30 backdrop-blur-lg border-b border-gray-200 dark:border-gray-100/10"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-9 h-9 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center"
                        >
                            <svg
                                class="w-5 h-5 text-white"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span
                            class="text-xl font-bold text-gray-900 dark:text-zinc-100 hidden sm:inline"
                            >LaravelSense</span
                        >
                        <span
                            class="text-lg font-bold text-gray-900 dark:text-zinc-100 sm:hidden"
                        >
                            LS
                        </span>
                    </div>

                    <!-- Search Bar - Hidden on mobile, shown on tablet+ -->
                    <div class="hidden md:flex flex-1 max-w-xl mx-8">
                        <div class="relative w-full">
                            <input
                                type="search"
                                placeholder="Search Laravel content..."
                                class="w-full pl-12 pr-4 py-3 bg-gray-100 dark:bg-zinc-800/30 border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 dark:focus:ring-red-700 focus:bg-white dark:focus:bg-zinc-800 dark:text-zinc-200 transition-all text-sm dark:placeholder:text-gray-100/30"
                            />
                            <svg
                                class="w-5 h-5 absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Mobile Search Button -->
                    <button
                        class="md:hidden p-2 text-gray-600 dark:text-zinc-500 dark:hover:text-gray-400 hover:text-gray-900 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </button>

                    <!-- Right Side - Auth/Non-Auth -->
                    <div
                        v-if="isAuthenticated"
                        class="flex items-center space-x-2"
                    >
                        <!-- Mobile Menu Button -->
                        <button
                            class="md:hidden p-2 text-gray-600 hover:text-gray-900 transition-colors rounded-lg hover:bg-gray-100 dark:text-zinc-500 dark:hover:text-gray-400 dark:hover:bg-zinc-800"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            </svg>
                        </button>

                        <!-- Desktop User Menu -->
                        <div class="hidden md:flex items-center space-x-3">
                            <div class="relative group">
                                <button
                                    class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center cursor-pointer hover:scale-105 transition-transform"
                                >
                                    <span
                                        class="text-white font-semibold text-sm"
                                        >{{
                                            user.name.charAt(0).toUpperCase()
                                        }}</span
                                    >
                                </button>
                                <!-- Dropdown -->
                                <div
                                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-800 rounded-xl shadow-lg border border-gray-200 dark:border-zinc-100/20 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200"
                                >
                                    <div class="py-2">
                                        <Link
                                            :href="route('profile.edit')"
                                            class="flex items-center px-4 py-3 text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-900/50 text-sm"
                                        >
                                            <svg
                                                class="w-4 h-4 mr-3 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                            </svg>
                                            Settings
                                        </Link>
                                        <div
                                            class="border-t border-gray-200 dark:border-gray-200/10 my-2"
                                        ></div>
                                        <Link
                                            :href="route('logout')"
                                            method="post"
                                            class="flex items-center px-4 py-3 text-red-600 dark:text-red-400 font-bold hover:bg-red-50 dark:hover:bg-red-950 dark:hover:text-red-200 text-sm w-full text-left"
                                        >
                                            <svg
                                                class="w-4 h-4 mr-3"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                                />
                                            </svg>
                                            Sign Out
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile User Avatar -->
                        <div class="md:hidden">
                            <button
                                class="w-10 h-10 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center cursor-pointer"
                            >
                                <span
                                    class="text-white font-semibold text-sm"
                                    >{{
                                        user.name.charAt(0).toUpperCase()
                                    }}</span
                                >
                            </button>
                        </div>
                    </div>

                    <!-- Non-Auth Navigation -->
                    <div v-else class="flex items-center space-x-2">
                        <Link
                            :href="route('login')"
                            class="text-gray-600 dark:text-zinc-300 hover:text-gray-900 dark:hover:text-gray-200 font-medium transition-colors text-sm px-4 py-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800"
                            >Login</Link
                        >
                        <Link
                            :href="route('register')"
                            class="bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-2.5 rounded-lg font-medium hover:from-red-600 hover:to-red-700 transition-all text-sm"
                        >
                            <span class="hidden sm:inline">Join Now</span>
                            <span class="sm:hidden">Join</span>
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 z-100">
            <!-- Authenticated User Layout -->
            <div
                v-if="isAuthenticated"
                class="flex flex-col lg:grid lg:grid-cols-4 gap-6 lg:gap-12"
            >
                <!-- Mobile Quick Actions Bar -->
                <div
                    class="lg:hidden bg-white dark:bg-zinc-900 rounded-xl border border-gray-200 dark:border-zinc-100/10 dark:shadow-lg p-4 mb-4"
                >
                    <div
                        class="flex items-center justify-between space-x-3 overflow-x-auto"
                    >
                        <button
                            @click="openAddSourceModal"
                            class="flex-shrink-0 bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 transition-colors flex items-center space-x-2"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                            <span class="hidden sm:inline">Add Source</span>
                        </button>
                        <Link
                            :href="route('library')"
                            class="flex-shrink-0 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-100/30 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 dark:hover:bg-zinc-700 transition-colors flex items-center space-x-2"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
                                />
                            </svg>
                            <span class="hidden sm:inline">Library</span>
                        </Link>
                        <Link
                            :href="route('liked-posts')"
                            class="flex-shrink-0 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-100/30 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 dark:hover:bg-zinc-700 transition-colors flex items-center space-x-2"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                />
                            </svg>
                            <span class="hidden sm:inline">Liked</span>
                        </Link>
                        <Link
                            :href="route('sources.index')"
                            class="flex-shrink-0 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-100/30 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 dark:hover:bg-zinc-700 transition-colors flex items-center space-x-2"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                />
                            </svg>
                            <span class="hidden sm:inline">Sources</span>
                        </Link>
                    </div>
                </div>

                <!-- Left Sidebar - Hidden on mobile, shown on desktop -->
                <div class="hidden lg:block lg:col-span-1">
                    <div class="sticky top-24 space-y-6">
                        <!-- Quick Actions -->
                        <div
                            class="bg-white dark:bg-zinc-800 dark:border-zinc-100/10 dark:text-zinc-100 rounded-xl border border-gray-200 p-6"
                        >
                            <h3
                                class="font-bold text-gray-900 dark:text-zinc-300 mb-4 text-lg"
                            >
                                Quick Actions
                            </h3>
                            <div class="space-y-3">
                                <button
                                    @click="openAddSourceModal"
                                    class="w-full bg-red-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-red-700 transition-colors flex items-center justify-center space-x-2"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                        />
                                    </svg>
                                    <span>Add Source</span>
                                </button>
                                <Link
                                    :href="route('library')"
                                    class="block w-full bg-gray-100 dark:bg-zinc-700/30 text-gray-700 dark:text-zinc-100/40 dark:hover:bg-zinc-900 px-4 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center"
                                >
                                    <svg
                                        class="w-4 h-4 inline mr-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
                                        />
                                    </svg>
                                    Bookmarks
                                </Link>
                                <Link
                                    :href="route('liked-posts')"
                                    class="block w-full bg-gray-100 text-gray-700 dark:bg-zinc-700/30 dark:text-zinc-100/40 dark:hover:bg-zinc-900 px-4 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center"
                                >
                                    <svg
                                        class="w-4 h-4 inline mr-2"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                        />
                                    </svg>
                                    Liked Posts
                                </Link>
                            </div>
                        </div>

                        <!-- Sources Widget -->
                        <SourcesWidget
                            :sources="userSources"
                            @add-source="openAddSourceModal"
                        />

                        <!-- Statistics -->
                        <div
                            class="bg-white dark:bg-zinc-800 dark:border-zinc-100/10 dark:text-zinc-100 rounded-xl border border-gray-200 p-6"
                        >
                            <h3
                                class="font-bold text-gray-900 dark:text-zinc-300 mb-4 text-lg"
                            >
                                Content Stats
                            </h3>
                            <div v-if="stats" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-zinc-400"
                                        >Blog Posts</span
                                    >
                                    <span
                                        class="text-xs text-gray-500 dark:text-zinc-500"
                                        >{{ stats.posts_by_type.posts }}</span
                                    >
                                </div>
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-zinc-400"
                                        >Videos</span
                                    >
                                    <span
                                        class="text-xs text-gray-500 dark:text-zinc-500"
                                        >{{ stats.posts_by_type.videos }}</span
                                    >
                                </div>
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-zinc-400"
                                        >Podcasts</span
                                    >
                                    <span
                                        class="text-xs text-gray-500 dark:text-zinc-500"
                                        >{{
                                            stats.posts_by_type.podcasts
                                        }}</span
                                    >
                                </div>
                            </div>
                            <div v-else class="space-y-3">
                                <!-- Loading skeleton for stats -->
                                <div class="flex items-center justify-between">
                                    <div
                                        class="h-4 bg-gray-200 dark:bg-zinc-600 rounded w-20 animate-pulse"
                                    ></div>
                                    <div
                                        class="h-3 bg-gray-200 dark:bg-zinc-600 rounded w-8 animate-pulse"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div
                                        class="h-4 bg-gray-200 dark:bg-zinc-600 rounded w-16 animate-pulse"
                                    ></div>
                                    <div
                                        class="h-3 bg-gray-200 dark:bg-zinc-600 rounded w-8 animate-pulse"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div
                                        class="h-4 bg-gray-200 dark:bg-zinc-600 rounded w-18 animate-pulse"
                                    ></div>
                                    <div
                                        class="h-3 bg-gray-200 dark:bg-zinc-600 rounded w-8 animate-pulse"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Trending -->
                        <div
                            class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-100/10 p-6"
                        >
                            <h3
                                class="font-bold text-gray-900 dark:text-zinc-300 mb-4 text-lg"
                            >
                                Trending
                            </h3>
                            <div v-if="stats?.trending_tags" class="space-y-3">
                                <div
                                    v-for="tag in stats.trending_tags.slice(
                                        0,
                                        5
                                    )"
                                    :key="tag.name"
                                    class="flex items-center justify-between"
                                >
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-zinc-400"
                                        >#{{ tag.name }}</span
                                    >
                                    <span
                                        class="text-xs text-gray-500 dark:text-zinc-500"
                                        >{{ tag.count }} posts</span
                                    >
                                </div>
                            </div>
                            <div v-else class="space-y-3">
                                <!-- Loading skeleton for trending -->
                                <div
                                    v-for="i in 5"
                                    :key="i"
                                    class="flex items-center justify-between"
                                >
                                    <div
                                        class="h-4 bg-gray-200 dark:bg-zinc-600 rounded w-24 animate-pulse"
                                    ></div>
                                    <div
                                        class="h-3 bg-gray-200 dark:bg-zinc-600 rounded w-12 animate-pulse"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Feed -->
                <div class="w-full lg:col-span-3">
                    <InfiniteScroll data="posts" :buffer="500">
                        <div
                            class="grid gap-4 sm:gap-6 md:grid-cols-2 lg:gap-8"
                        >
                            <article
                                v-for="post in posts.data"
                                :key="post.id"
                                class="bg-white dark:bg-zinc-800/30 rounded-2xl border border-gray-200 dark:border-zinc-100/10 hover:shadow-lg transition-all duration-200"
                            >
                                <!-- Video Thumbnail (if video) -->
                                <div
                                    v-if="post.type.value === 'video'"
                                    class="relative rounded-t-2xl overflow-hidden"
                                >
                                    <div
                                        class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-zinc-600 dark:to-zinc-700 flex items-center justify-center"
                                    >
                                        <img
                                            v-if="post.featured_image"
                                            :src="post.featured_image"
                                            :alt="post.title"
                                            class="w-full h-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="w-16 h-16 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg"
                                        >
                                            <svg
                                                class="w-6 h-6 text-red-600 ml-1"
                                                fill="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div
                                        v-if="post.duration"
                                        class="absolute bottom-3 right-3 bg-black/70 text-white px-2 py-1 rounded text-xs"
                                    >
                                        {{ post.duration }}
                                    </div>
                                    <div
                                        class="absolute top-3 left-3 px-2 py-1 rounded text-xs font-medium text-white"
                                        :class="
                                            getPostTypeColors(post.type.color)
                                        "
                                    >
                                        {{ post.type.label.toUpperCase() }}
                                    </div>
                                </div>

                                <div class="p-4 sm:p-6">
                                    <!-- Source Header -->
                                    <div
                                        class="flex items-center justify-between mb-4"
                                    >
                                        <div
                                            class="flex items-center space-x-3"
                                        >
                                            <div
                                                class="w-9 h-9 bg-gradient-to-br from-gray-400 to-gray-500 dark:from-zinc-700 dark:to-zinc-600 rounded-full flex items-center justify-center"
                                            >
                                                <span
                                                    v-if="post.author.name"
                                                    class="text-white dark:text-zinc-300 font-semibold text-sm"
                                                >
                                                    {{
                                                        post.author.name
                                                            .charAt(0)
                                                            .toUpperCase()
                                                    }}
                                                </span>
                                                <img
                                                    v-else-if="
                                                        post.author.avatar
                                                    "
                                                    :src="post.author.avatar"
                                                    :alt="post.author.name"
                                                    class="w-9 h-9 rounded-full"
                                                />
                                            </div>
                                            <div>
                                                <div
                                                    class="font-semibold text-gray-900 dark:text-zinc-400 text-sm"
                                                >
                                                    {{
                                                        post.author.name ||
                                                        "Anonymous"
                                                    }}
                                                </div>
                                                <div
                                                    class="text-gray-500 dark:text-zinc-100/40 text-sm"
                                                >
                                                    {{ post.published_at }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div
                                            v-if="isAuthenticated"
                                            class="flex items-center space-x-1"
                                        >
                                            <!-- Bookmark Button -->
                                            <button
                                                @click="toggleBookmark(post)"
                                                :class="
                                                    post.is_bookmarked
                                                        ? 'text-red-600'
                                                        : 'text-gray-400 dark:text-zinc-500 hover:text-red-600'
                                                "
                                                class="p-3 transition-colors rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 active:scale-95"
                                                title="Save for later"
                                            >
                                                <svg
                                                    class="w-5 h-5"
                                                    :fill="
                                                        post.is_bookmarked
                                                            ? 'currentColor'
                                                            : 'none'
                                                    "
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Mark as Seen Button -->
                                            <button
                                                v-if="!post.is_seen"
                                                @click="markAsSeen(post)"
                                                class="p-3 text-gray-400 dark:text-zinc-500 hover:text-green-600 transition-colors rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 active:scale-95"
                                                title="Mark as seen"
                                            >
                                                <svg
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Mark as Unseen Button -->
                                            <button
                                                v-if="post.is_seen"
                                                @click="markAsUnseen(post)"
                                                class="p-3 text-green-600 hover:text-gray-400 transition-colors rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 active:scale-95"
                                                title="Mark as unseen"
                                            >
                                                <svg
                                                    class="w-5 h-5"
                                                    fill="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <Link
                                        :href="route('posts.show', post.slug)"
                                    >
                                        <h3
                                            class="text-xl font-bold text-gray-900 dark:text-zinc-300 mb-4 leading-tight hover:text-red-600 cursor-pointer transition-colors"
                                        >
                                            {{ post.title }}
                                        </h3>
                                    </Link>

                                    <p
                                        v-if="post.excerpt"
                                        class="text-gray-600 dark:text-zinc-100/40 mb-6 leading-relaxed line-clamp-3"
                                    >
                                        {{ post.excerpt }}
                                    </p>

                                    <!-- Podcast Player (if podcast) -->
                                    <div
                                        v-if="post.type.value === 'podcast'"
                                        class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-3 mb-4"
                                    >
                                        <div
                                            class="flex items-center space-x-3"
                                        >
                                            <button
                                                class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center shadow-sm"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-white ml-1"
                                                    fill="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </button>
                                            <div class="flex-1">
                                                <div
                                                    class="flex items-center justify-between text-xs text-gray-600 mb-1"
                                                >
                                                    <span>0:00</span>
                                                    <span>{{
                                                        post.duration || "0:00"
                                                    }}</span>
                                                </div>
                                                <div
                                                    class="w-full bg-gray-200 rounded-full h-1"
                                                >
                                                    <div
                                                        class="bg-indigo-600 h-1 rounded-full"
                                                        style="width: 0%"
                                                    ></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tags -->
                                    <div
                                        v-if="post.tags && post.tags.length"
                                        class="flex items-center flex-wrap gap-2 mb-6"
                                    >
                                        <span
                                            v-for="tag in post.tags.slice(0, 3)"
                                            :key="tag"
                                            class="bg-gray-100 dark:bg-zinc-800 text-gray-800 dark:text-zinc-100/40 px-3 py-1.5 rounded-full text-xs font-medium"
                                        >
                                            {{ tag }}
                                        </span>
                                        <span
                                            v-if="post.duration"
                                            class="text-gray-500 dark:text-zinc-100/40 text-sm ml-auto"
                                            >{{ post.duration }}</span
                                        >
                                    </div>

                                    <!-- Actions -->
                                    <div
                                        class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-zinc-100/10"
                                    >
                                        <div
                                            class="flex items-center space-x-6 text-sm text-gray-500 dark:text-zinc-500"
                                        >
                                            <!-- Like Button -->
                                            <button
                                                v-if="isAuthenticated"
                                                @click="toggleLike(post)"
                                                :class="
                                                    post.is_liked
                                                        ? 'text-red-500'
                                                        : 'hover:text-red-500'
                                                "
                                                class="flex items-center space-x-2 transition-colors"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    :fill="
                                                        post.is_liked
                                                            ? 'currentColor'
                                                            : 'none'
                                                    "
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                                    />
                                                </svg>
                                                <span>{{
                                                    post.likes_count
                                                }}</span>
                                            </button>

                                            <!-- Bookmark Button -->
                                            <button
                                                v-if="isAuthenticated"
                                                @click="toggleBookmark(post)"
                                                :class="
                                                    post.is_bookmarked
                                                        ? 'text-red-500'
                                                        : 'hover:text-red-500'
                                                "
                                                class="flex items-center space-x-2 transition-colors"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    :fill="
                                                        post.is_bookmarked
                                                            ? 'currentColor'
                                                            : 'none'
                                                    "
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                        <Link
                                            :href="
                                                route('posts.show', post.slug)
                                            "
                                            class="text-red-600 hover:text-red-700 font-medium"
                                        >
                                            {{ getPostAction(post) }}
                                        </Link>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </InfiniteScroll>
                </div>
            </div>

            <!-- Non-Authenticated User Layout -->
            <div
                v-else
                class="flex flex-col lg:grid lg:grid-cols-4 gap-6 lg:gap-12"
            >
                <!-- Welcome Banner -->
                <div
                    class="lg:col-span-4 bg-gradient-to-r from-red-500 to-red-600 dark:bg-[radial-gradient(ellipse_at_top_left,_var(--tw-gradient-stops))] dark:from-red-900 dark:via-red-800 dark:to-red-950 rounded-2xl p-6 sm:p-8 text-white text-center"
                >
                    <h1 class="text-xl sm:text-2xl font-bold mb-2">
                        Welcome to LaravelSense
                    </h1>
                    <p class="text-red-100 mb-4 sm:mb-6 text-sm sm:text-base">
                        Discover the latest Laravel content from top creators in
                        the community
                    </p>
                    <div
                        class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center"
                    >
                        <Link
                            :href="route('login')"
                            class="bg-white text-red-600 px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold hover:bg-gray-50 transition-colors text-sm sm:text-base"
                        >
                            Sign In
                        </Link>
                        <Link
                            :href="route('register')"
                            class="bg-red-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-semibold hover:bg-red-800 transition-colors text-sm sm:text-base"
                        >
                            Join Free
                        </Link>
                    </div>
                </div>

                <!-- Left Sidebar - Stats and Info -->
                <div class="hidden lg:block lg:col-span-1">
                    <div class="sticky top-24 space-y-6">
                        <!-- Guest Call to Action -->
                        <div
                            class="bg-white dark:bg-zinc-800 dark:border-zinc-100/10 dark:text-zinc-100 rounded-xl border border-gray-200 p-6"
                        >
                            <h3
                                class="font-bold text-gray-900 dark:text-zinc-300 mb-4 text-lg"
                            >
                                Join the Community
                            </h3>
                            <div class="space-y-3">
                                <Link
                                    :href="route('register')"
                                    class="w-full bg-red-600 text-white px-4 py-3 rounded-lg font-medium hover:bg-red-700 transition-colors flex items-center justify-center space-x-2 text-xs"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                        />
                                    </svg>
                                    <span>Sign Up Free</span>
                                </Link>
                                <Link
                                    :href="route('login')"
                                    class="block w-full bg-gray-100 dark:bg-zinc-700/30 text-gray-700 dark:text-zinc-100/40 dark:hover:bg-zinc-900 px-4 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center text-xs"
                                >
                                    Already have an account?
                                </Link>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div
                            class="bg-white dark:bg-zinc-800 dark:border-zinc-100/10 dark:text-zinc-100 rounded-xl border border-gray-200 p-6"
                        >
                            <h3
                                class="font-bold text-gray-900 dark:text-zinc-300 mb-4 text-lg"
                            >
                                Content Stats
                            </h3>
                            <div v-if="stats" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-zinc-300"
                                        >Blog Posts</span
                                    >
                                    <span
                                        class="text-xs text-gray-500 dark:text-zinc-400"
                                        >{{ stats.posts_by_type.posts }}</span
                                    >
                                </div>
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-zinc-300"
                                        >Videos</span
                                    >
                                    <span
                                        class="text-xs text-gray-500 dark:text-zinc-400"
                                        >{{ stats.posts_by_type.videos }}</span
                                    >
                                </div>
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-zinc-300"
                                        >Podcasts</span
                                    >
                                    <span
                                        class="text-xs text-gray-500 dark:text-zinc-400"
                                        >{{
                                            stats.posts_by_type.podcasts
                                        }}</span
                                    >
                                </div>
                            </div>
                            <div v-else class="space-y-3">
                                <!-- Loading skeleton for stats -->
                                <div class="flex items-center justify-between">
                                    <div
                                        class="h-4 bg-gray-200 dark:bg-zinc-500 rounded w-20 animate-pulse"
                                    ></div>
                                    <div
                                        class="h-3 bg-gray-200 dark:bg-zinc-500 rounded w-8 animate-pulse"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div
                                        class="h-4 bg-gray-200 dark:bg-zinc-500 rounded w-16 animate-pulse"
                                    ></div>
                                    <div
                                        class="h-3 bg-gray-200 dark:bg-zinc-500 rounded w-8 animate-pulse"
                                    ></div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div
                                        class="h-4 bg-gray-200 dark:bg-zinc-500 rounded w-18 animate-pulse"
                                    ></div>
                                    <div
                                        class="h-3 bg-gray-200 dark:bg-zinc-500 rounded w-8 animate-pulse"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Trending -->
                        <div
                            class="bg-white dark:bg-zinc-800 dark:border-zinc-100/10 dark:text-zinc-100 rounded-xl border border-gray-200 p-6"
                        >
                            <h3
                                class="font-bold text-gray-900 dark:text-zinc-300 mb-4 text-lg"
                            >
                                Trending
                            </h3>
                            <div v-if="stats?.trending_tags" class="space-y-3">
                                <div
                                    v-for="tag in stats.trending_tags.slice(
                                        0,
                                        5
                                    )"
                                    :key="tag.name"
                                    class="flex items-center justify-between"
                                >
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-zinc-400"
                                        >#{{ tag.name }}</span
                                    >
                                    <span
                                        class="text-xs text-gray-500 dark:text-zinc-500"
                                        >{{ tag.count }} posts</span
                                    >
                                </div>
                            </div>
                            <div v-else class="space-y-3">
                                <!-- Loading skeleton for trending -->
                                <div
                                    v-for="i in 5"
                                    :key="i"
                                    class="flex items-center justify-between"
                                >
                                    <div
                                        class="h-4 bg-gray-200 dark:bg-zinc-500 rounded w-24 animate-pulse"
                                    ></div>
                                    <div
                                        class="h-3 bg-gray-200 dark:bg-zinc-500 rounded w-12 animate-pulse"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Feed -->
                <div class="w-full lg:col-span-3">
                    <InfiniteScroll data="posts" :buffer="500">
                        <div
                            class="grid gap-4 sm:gap-6 md:grid-cols-2 lg:gap-8"
                        >
                            <article
                                v-for="post in posts.data"
                                :key="post.id"
                                class="bg-white dark:bg-zinc-800/30 rounded-2xl border border-gray-200 dark:border-zinc-100/10 hover:shadow-lg transition-all duration-200"
                            >
                                <!-- Video Thumbnail (if video) -->
                                <div
                                    v-if="post.type.value === 'video'"
                                    class="relative rounded-t-2xl overflow-hidden"
                                >
                                    <div
                                        class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center"
                                    >
                                        <img
                                            v-if="post.featured_image"
                                            :src="post.featured_image"
                                            :alt="post.title"
                                            class="w-full h-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="w-16 h-16 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg"
                                        >
                                            <svg
                                                class="w-6 h-6 text-red-600 ml-1"
                                                fill="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div
                                        v-if="post.duration"
                                        class="absolute bottom-3 right-3 bg-black/70 text-white px-2 py-1 rounded text-xs"
                                    >
                                        {{ post.duration }}
                                    </div>
                                    <div
                                        class="absolute top-3 left-3 px-2 py-1 rounded text-xs font-medium text-white"
                                        :class="
                                            getPostTypeColors(post.type.color)
                                        "
                                    >
                                        {{ post.type.label.toUpperCase() }}
                                    </div>
                                </div>

                                <div class="p-4 sm:p-6">
                                    <!-- Source Header -->
                                    <div
                                        class="flex items-center justify-between mb-4"
                                    >
                                        <div
                                            class="flex items-center space-x-3"
                                        >
                                            <div
                                                class="w-9 h-9 bg-gradient-to-br from-gray-400 to-gray-500 dark:from-zinc-700 dark:to-zinc-600 rounded-full flex items-center justify-center"
                                            >
                                                <span
                                                    v-if="post.author.name"
                                                    class="text-white dark:text-zinc-300 font-semibold text-sm"
                                                >
                                                    {{
                                                        post.author.name
                                                            .charAt(0)
                                                            .toUpperCase()
                                                    }}
                                                </span>
                                                <img
                                                    v-else-if="
                                                        post.author.avatar
                                                    "
                                                    :src="post.author.avatar"
                                                    :alt="post.author.name"
                                                    class="w-9 h-9 rounded-full"
                                                />
                                            </div>
                                            <div>
                                                <div
                                                    class="font-semibold text-gray-900 dark:text-zinc-400 text-sm"
                                                >
                                                    {{
                                                        post.author.name ||
                                                        "Anonymous"
                                                    }}
                                                </div>
                                                <div
                                                    class="text-gray-500 dark:text-zinc-100/40 text-sm"
                                                >
                                                    {{ post.published_at }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Buttons for Guests (Show register prompt) -->
                                        <div
                                            class="flex items-center space-x-1"
                                        >
                                            <!-- Bookmark Button -->
                                            <button
                                                @click="showGuestPrompt"
                                                class="text-gray-400 dark:text-zinc-500 hover:text-red-600 p-3 transition-colors rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 active:scale-95"
                                                title="Sign up to save posts"
                                            >
                                                <svg
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- Mark as Seen Button -->
                                            <button
                                                @click="showGuestPrompt"
                                                class="p-3 text-gray-400 dark:text-zinc-500 hover:text-green-600 transition-colors rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 active:scale-95"
                                                title="Sign up to track reading progress"
                                            >
                                                <svg
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <Link
                                        :href="route('posts.show', post.slug)"
                                    >
                                        <h3
                                            class="text-xl font-bold text-gray-900 dark:text-zinc-300 mb-4 leading-tight hover:text-red-600 cursor-pointer transition-colors"
                                        >
                                            {{ post.title }}
                                        </h3>
                                    </Link>

                                    <p
                                        v-if="post.excerpt"
                                        class="text-gray-600 dark:text-zinc-100/40 mb-6 leading-relaxed line-clamp-3"
                                    >
                                        {{ post.excerpt }}
                                    </p>

                                    <!-- Podcast Player (if podcast) -->
                                    <div
                                        v-if="post.type.value === 'podcast'"
                                        class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-3 mb-4"
                                    >
                                        <div
                                            class="flex items-center space-x-3"
                                        >
                                            <button
                                                class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center shadow-sm"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-white ml-1"
                                                    fill="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </button>
                                            <div class="flex-1">
                                                <div
                                                    class="flex items-center justify-between text-xs text-gray-600 mb-1"
                                                >
                                                    <span>0:00</span>
                                                    <span>{{
                                                        post.duration || "0:00"
                                                    }}</span>
                                                </div>
                                                <div
                                                    class="w-full bg-gray-200 rounded-full h-1"
                                                >
                                                    <div
                                                        class="bg-indigo-600 h-1 rounded-full"
                                                        style="width: 0%"
                                                    ></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tags -->
                                    <div
                                        v-if="post.tags && post.tags.length"
                                        class="flex items-center flex-wrap gap-2 mb-6"
                                    >
                                        <span
                                            v-for="tag in post.tags.slice(0, 3)"
                                            :key="tag"
                                            class="bg-gray-100 dark:bg-zinc-800 text-gray-800 dark:text-zinc-100/40 px-3 py-1.5 rounded-full text-xs font-medium"
                                        >
                                            {{ tag }}
                                        </span>
                                        <span
                                            v-if="post.duration"
                                            class="text-gray-500 text-sm ml-auto"
                                            >{{ post.duration }}</span
                                        >
                                    </div>

                                    <!-- Actions -->
                                    <div
                                        class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-zinc-100/10"
                                    >
                                        <div
                                            class="flex items-center space-x-6 text-sm text-gray-500"
                                        >
                                            <!-- Like Button -->
                                            <button
                                                @click="showGuestPrompt"
                                                class="flex items-center space-x-2 hover:text-red-500 transition-colors"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                                                    />
                                                </svg>
                                                <span>{{
                                                    post.likes_count
                                                }}</span>
                                            </button>

                                            <!-- Bookmark Button -->
                                            <button
                                                @click="showGuestPrompt"
                                                class="flex items-center space-x-2 hover:text-blue-500 transition-colors"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                        <Link
                                            :href="
                                                route('posts.show', post.slug)
                                            "
                                            class="text-red-600 hover:text-red-700 font-medium"
                                        >
                                            {{ getPostAction(post) }}
                                        </Link>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </InfiniteScroll>
                </div>
            </div>
        </div>

        <!-- Guest Prompt Modal -->
        <GuestPromptModal
            :show="showGuestModal"
            @close="closeGuestModal"
            maxWidth="md"
        />

        <!-- Add Source Modal -->
        <Modal
            :show="showAddSourceModal"
            @close="closeAddSourceModal"
            maxWidth="lg"
        >
            <div class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">
                        Add New Source
                    </h2>
                    <button
                        @click="closeAddSourceModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-100 rounded-xl"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAddSource">
                    <div class="mb-6">
                        <InputLabel
                            for="source_url"
                            value="Website URL"
                            class="block text-sm font-semibold text-gray-700 mb-3"
                        />
                        <TextInput
                            id="source_url"
                            v-model="addSourceForm.url"
                            type="url"
                            placeholder="https://example.com"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="addSourceForm.errors.url"
                        />
                        <p class="mt-3 text-sm text-gray-500">
                            Enter any website URL and we'll find the content
                            automatically
                        </p>
                    </div>

                    <div
                        class="bg-blue-50 border border-blue-200 rounded-2xl p-6 mb-8"
                    >
                        <div class="flex items-start">
                            <svg
                                class="w-6 h-6 text-blue-600 mr-4 mt-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            <div>
                                <h4 class="font-semibold text-blue-900 mb-2">
                                    How it works
                                </h4>
                                <p class="text-sm text-blue-800">
                                    Just paste any website URL. We'll
                                    automatically detect and add their content
                                    feeds for you.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <SecondaryButton
                            @click="closeAddSourceModal"
                            class="flex-1"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="addSourceForm.processing"
                            class="flex-1 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800"
                        >
                            <svg
                                class="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                            Add Source
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Toast Container -->
        <ToastContainer />
    </div>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
