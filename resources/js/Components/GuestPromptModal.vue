<template>
    <Modal :show="show" @close="$emit('close')" :maxWidth="maxWidth">
        <div class="p-6 sm:p-8">
            <div class="text-center">
                <div
                    class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-4"
                >
                    <svg
                        class="w-8 h-8 text-white"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    Join LaravelSense
                </h2>
                <p class="text-gray-600 mb-6">
                    Sign up to unlock all features like saving posts, tracking
                    your reading progress, and personalizing your feed.
                </p>

                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-3">
                        What you'll get:
                    </h3>
                    <div class="space-y-2 text-sm text-gray-700">
                        <div
                            v-for="feature in features"
                            :key="feature"
                            class="flex items-center"
                        >
                            <svg
                                class="w-4 h-4 text-green-600 mr-2 flex-shrink-0"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            {{ feature }}
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <Link
                        :href="route('register')"
                        class="flex-1 bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-red-600 hover:to-red-700 transition-all text-center"
                    >
                        Create Free Account
                    </Link>
                    <Link
                        :href="route('login')"
                        class="flex-1 bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-200 transition-colors text-center"
                    >
                        Sign In
                    </Link>
                </div>

                <button
                    @click="$emit('close')"
                    class="mt-4 text-sm text-gray-500 hover:text-gray-700 transition-colors"
                >
                    Continue browsing
                </button>
            </div>
        </div>
    </Modal>
</template>
<script setup>
import { Link } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: {
        type: Boolean,
        required: true,
    },
    maxWidth: {
        type: String,
        default: "md",
    },
});

const emit = defineEmits(["close"]);

const features = [
    "Save and bookmark your favorite posts",
    "Track your reading progress",
    "Add your own content sources",
    "Personalized content feed",
];
</script>
