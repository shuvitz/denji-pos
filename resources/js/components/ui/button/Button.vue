<script setup>
import { cn } from "@/lib/utils"
import { buttonVariants } from "."

const props = defineProps({
    as: { type: String, default: "button" },
    asChild: { type: Boolean, default: false },
    variant: { type: String, default: "default" },
    size: { type: String, default: "default" },
    class: { type: String, default: "" },
})
</script>

<template>
    <component
        v-if="!props.asChild"
        :is="props.as"
        v-bind="$attrs"
        :class="cn(buttonVariants({ variant: props.variant, size: props.size }), props.class)"
    >
        <slot />
    </component>
    <span
        v-else
        v-bind="$attrs"
        :class="cn(buttonVariants({ variant: props.variant, size: props.size }), props.class)"
    >
        <slot />
    </span>
</template>
