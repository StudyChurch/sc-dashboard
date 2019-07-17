<template>
	<div class="sc-group">

		<div class="row">
			<div class="col-lg-4 groups-sidebar">
				<card class="card-user">
					<div slot="image" class="image">
						<img src="@/assets/img/bg-bible.jpg" alt="...">
					</div>
					<div>
						<div class="author">
							<font-awesome-icon icon="users" class="avatar border-gray"></font-awesome-icon>
							<router-link :to="'/organizations/' + group.organization.slug" v-if="groupData.parent_id"><h6 class="title" v-html="group.organization.name"></h6></router-link>
							<h5 class="title" v-html="groupData.name"></h5>
							<p class="description" v-show="showGroupDesc" v-html="groupData.description.rendered"></p>
							<p class="description" v-show="showGroupDesc">
								<a href="#" @click.stop="showGroupDesc=false">Hide details</a></p>
							<p class="description" v-show="!showGroupDesc">
								<a href="#" @click.stop="showGroupDesc=true">Show details</a></p>
						</div>

						<div v-loading="loadingTodos" class="mobile-only">
							<ul slot="raw-content" class="list-group list-group-flush">
								<li :class="'list-group-item'" style="text-align: center;">
									<h6>Upcoming To-Do</h6>
									<p v-for="lesson in firstTodo.lessons" :key="lesson.id">
										<router-link :to="'/groups/' + $route.params.slug + $root.cleanLink(lesson.link)">
											<i class="now-ui-icons design_bullet-list-67"></i>&nbsp;
											<span v-html="lesson.title"></span></router-link>
									</p>
								</li>
							</ul>
						</div>
					</div>
				</card>

				<card class="card-chart d-lg-block d-none" no-footer-line v-loading="loadingTodos" style="min-height: 200px;">

					<div slot="header">
						<h5 class="card-title">Upcoming To-Dos</h5>
					</div>

					<ul slot="raw-content" class="list-group list-group-flush">
						<li v-for="data in todoData" :key="data.id" :class="'list-group-item'">
							&nbsp;
							<h6>Due Date: {{data.date}}</h6>
							<p v-for="lesson in data.lessons" :key="lesson.id">
								<router-link :to="'/groups/' + $route.params.slug + $root.cleanLink(lesson.link)">
									<i class="now-ui-icons design_bullet-list-67"></i>&nbsp;
									<span v-html="lesson.title"></span></router-link>
							</p>
							<p v-html="data.content"></p>
						</li>
					</ul>

					<p v-if="!todoData.length && !loadingTodos">There are no upcoming to-dos.</p>

				</card>

				<card class="card-chart d-lg-block d-none" no-footer-line v-loading="loadingStudies">

					<div slot="header">
						<h5 class="card-title">Studies</h5>
					</div>

					<div class="table-responsive">
						<n-table :data="groupData.studies">
							<template slot-scope="{row}">
								<td>
									<router-link :to="'/groups/' + $route.params.slug + $root.cleanLink(row.link)" v-html="row.title.rendered"></router-link>
								</td>
							</template>
						</n-table>
					</div>

				</card>

				<card class="card-chart d-lg-block d-none" no-footer-line v-loading="loadingMembers">

					<div slot="header">
						<h5 class="card-title">Members</h5>
						<drop-down v-if="false" :hide-arrow="true" position="right">
							<n-button slot="title" class="dropdown-toggle no-caret" type="neutral" round icon>
								<i class="now-ui-icons loader_gear"></i>
							</n-button>

							<router-link :to="'/groups/' + this.$route.params.slug + '/members/'" class="dropdown-item">Manage Members</router-link>
							<a class="dropdown-item" href="#">Invite a new member</a>
						</drop-down>
					</div>

					<div class="table-responsive">
						<n-table :data="groupData.members.members" v-if="! loadingMembers">
							<template slot-scope="{row}">
								<td>
									<img class="avatar" :src="getAvatar(row)" />
								</td>
								<td v-html="getName(row)"></td>
							</template>
						</n-table>
					</div>

				</card>
			</div>

			<div class="col-lg-8" v-loading="!loaded" style="min-height: 300px;">

				<el-menu :default-active="defaultActiveTab" class="el-menu-demo" mode="horizontal" :router="true">
					<el-menu-item :index="'/groups/' + this.$route.params.slug + '/'">
						<font-awesome-icon icon="comments"></font-awesome-icon>&nbsp;&nbsp;<span>Discussion</span>
					</el-menu-item>
					<el-menu-item :index="'/groups/' + this.$route.params.slug + '/assignments/'">
						<font-awesome-icon icon="list"></font-awesome-icon>&nbsp;&nbsp;<span>To-Dos</span></el-menu-item>
					<el-menu-item :index="'/groups/' + this.$route.params.slug + '/studies/'">
						<font-awesome-icon icon="book"></font-awesome-icon>&nbsp;&nbsp;<span>Studies</span>
					</el-menu-item>
					<el-menu-item :index="'/groups/' + this.$route.params.slug + '/members/'">
						<font-awesome-icon icon="user"></font-awesome-icon>&nbsp;&nbsp;<span>Members</span>
					</el-menu-item>
					<el-menu-item :index="'/groups/' + this.$route.params.slug + '/settings/'" v-if="isGroupAdmin()">
						<font-awesome-icon icon="cogs"></font-awesome-icon>&nbsp;&nbsp;<span>Settings</span>
					</el-menu-item>
				</el-menu>

				<br />

				<router-view :groupData.sync="groupData" v-if="loaded"></router-view>

			</div>
		</div>

	</div>
</template>
<script>
  import {
    Card,
    Table as NTable
  } from 'src/components'

  import { Menu, MenuItem } from 'element-ui';
  import { mapState, mapGetters } from 'vuex';

  function getDefaultData () {
    return {
      loadingGroups : false,
      loadingStudies: false,
      loadingTodos  : true,
      loadingMembers: true,
      showGroupDesc : false,
      todoData      : [],
		firstTodo: '',
    }
  }

  export default {
    components: {
      'el-menu'     : Menu,
      'el-menu-item': MenuItem,
      Card,
      NTable
    },
    data      : getDefaultData,
    mounted() {
      this.setupCurrentGroup();
    },
    watch     : {
      '$route' (to, from) {
        if (to.params.slug === from.params.slug) {
          return;
        }

        this.setupCurrentGroup();
      }
    },
    computed  : {
      ...mapState(['user', 'group']),
      ...mapGetters('group', ['isOrgAdmin', 'isGroupAdmin']),
      ...mapGetters('user', ['getAvatar', 'getName']),

      defaultActiveTab() {
        return (
          undefined === this.$route.params.study
        ) ? this.$route.path : '/groups/' + this.$route.params.slug + '/studies/';
      },

      groupData() {
        return this.group.group;
      },

      loaded() {
        return this.groupData.id && (!this.groupData.parent_id || this.group.organization.id === this.groupData.parent_id);
	  },

    },
    methods   : {
      setupCurrentGroup () {
        this.$store
          .dispatch('group/fetchGroup', {id: this.$route.params.slug, key: 'slug'})
          .then(() => {
            this.getGroupTodos();
            this.getMembers();
          });
      },
      getGroupTodos () {
        this.loadingTodos = true;
        this.$http
          .get(
            '/wp-json/studychurch/v1/assignments?group_id=' + this.groupData.id)
          .then(response => {
              this.todoData = response.data;
              if ( this.todoData.length > 0 ) {
                  this.firstTodo = this.todoData[0];
			  }
          })
          .finally(() => this.loadingTodos = false)
      },
      getMembers () {
        this.loadingMembers = true;
        return this.$store
          .dispatch('user/fetchUsersByID', this.group.group.members.members)
          .then(() => {
            this.loadingMembers = false;
          });
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
