<script setup>
import { reactiveOmit } from "@vueuse/core";
import { MinusIcon } from "lucide-vue-next";
import { useForwardProps } from "reka-ui";
import { cn } from "@/lib/utils";

const props = defineProps({
  class: {
    type: [Boolean, null, String, Object, Array],
    required: false,
    skipCheck: true,
  },
});

const delegatedProps = reactiveOmit(props, "class");
const forwarded = useForwardProps(delegatedProps);
</script>

<template>
  <div
    data-slot="input-otp-separator"
    role="separator"
    v-bind="forwarded"
    :class="
      cn('[&_svg:not([class*=size-])]:size-4 flex items-center', props.class)
    "
  >
    <slot>
      <MinusIcon />
    </slot>
  </div>
</template>
