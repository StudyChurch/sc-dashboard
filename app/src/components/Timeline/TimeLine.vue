<template>
  <card class="card-timeline" plain>
    <ul class="timeline" :class="{'timeline-simple': type === 'simple'}">
      <slot></slot>
    </ul>
  </card>
</template>
<script>
  export default {
    name: 'time-line',
    props: {
      type: {
        type: String,
        default: ''
      }
    }
  }
</script>
<style>
  .card-timeline .timeline:before {
    top: 50px;
    bottom: 50px;
  }
</style>
