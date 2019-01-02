<template>

	<div class="sc-group--assignments" v-loading="loadingTodos" style="min-height: 200px;">

		<card v-for="data in todoData" :class="'card'">
			<router-link class="category" v-html="getGroupById(data.group).name" :to="'/groups/' + getGroupById(data.group).slug + '/'"></router-link>
			<h6>Due Date: {{data.date}}</h6>
			<p v-for="lesson in data.lessons">
				<router-link :to="'/groups/' + getGroupById(data.group).slug + $root.cleanLink(lesson.link)">
					<i class="now-ui-icons design_bullet-list-67"></i>&nbsp;
					<span v-html="lesson.title"></span></router-link>
			</p>
			<p v-html="data.content"></p>
		</card>

	</div>

</template>
<script>
  import {
    Card,
    Table as NTable,
    Button
  } from 'src/components'
  import AssignmentService from '@/services/AssignmentService';
  import { mapState, mapGetters } from 'vuex';

  function getDefaultData () {
    return {
      loadingTodos: true,
      loadingMore : false,
      todoData    : [],
      todoPage    : 1,
    }
  }

  export default {
    components: {
      Card,
      NTable,
      Button
    },
    props     : {},
    data      : getDefaultData,
    mounted() {
      this.getGroupTodos();
    },
    computed: {
      ...mapState(['user', 'group']),
      ...mapGetters('group', ['getGroupById'])
    },
    methods   : {
      getGroupTodos () {
        this.loadingTodos = true;
        AssignmentService
          .getAssignments(
            '/wp-json/studychurch/v1/assignments')
          .then(response => (
            this.todoData = response.data
          ))
          .finally(() => this.loadingTodos = false)
      },
      reset (keep) {
        let def = getDefaultData();
        def[keep] = this[keep];
        Object.assign(this.$data, def);
      }
    }
  }
</script>
<style>
</style>