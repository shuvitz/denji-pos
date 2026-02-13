<script setup>
import { Label as RekaLabel } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps({
    class: { type: String, default: "" },
    for: { type: String, default: undefined },
})
</script>

<template>
    <RekaLabel
        :for="props.for"
        :class="cn('text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70', props.class)"
    >
        <slot />
    </RekaLabel>
</template>
