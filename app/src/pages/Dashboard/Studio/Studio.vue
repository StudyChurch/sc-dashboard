<template>
	<div class="sc-studio">
		<router-view></router-view>
	</div>
</template>
<script>
  import {
    Card,
    Table as NTable,
    Progress as NProgress,
    AnimatedNumber,
    TimeLine,
    TimeLineItem,
    ActivityForm
  } from 'src/components';

  import { Select, Option } from 'element-ui';
  import { mapState, mapGetters } from 'vuex';

  let he = require('he');

  export default {
    components: {
      Card,
      NTable,
      NProgress,
      AnimatedNumber,
      TimeLine,
      TimeLineItem,
      ActivityForm,
      'el-select': Select,
      'el-option': Option
    },
    mounted() {
    },
    watch     : {
    },
    computed  : {

    }
  }
</script>
<style scoped>

</style>
