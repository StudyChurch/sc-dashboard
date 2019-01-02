<template>
	<div class="sc-group">

		<div class="row">
			<div class="col-lg-4">
				<card class="card-user">
					<div slot="image" class="image">
						<img src="@/assets/img/bg-bible.jpg" alt="...">
					</div>
					<div>
						<div class="author">
							<font-awesome-icon icon="users" class="avatar border-gray"></font-awesome-icon>
							<router-link :to="'/groups/' + $route.params.slug + '/'">
								<h5 class="title" v-html="groupData.name"></h5></router-link>
							<p class="description" v-show="showGroupDesc" v-html="groupData.description.rendered"></p>
							<p class="description" v-show="showGroupDesc">
								<a href="#" @click.stop="showGroupDesc=false">Hide details</a></p>
							<p class="description" v-show="!showGroupDesc">
								<a href="#" @click.stop="showGroupDesc=true">Show details</a></p>
						</div>
					</div>
				</card>

				<card class="card-chart d-lg-block d-none" no-footer-line v-loading="loadingTodos" style="min-height: 200px;">

					<div slot="header">
						<h5 class="card-title">Upcoming Todos</h5>
					</div>

					<ul slot="raw-content" class="list-group list-group-flush">
						<li v-for="data in todoData" :class="'list-group-item'">
							&nbsp;
							<h6>Due Date: {{data.date}}</h6>
							<p v-for="lesson in data.lessons">
								<router-link :to="'/groups/' + $route.params.slug + $root.cleanLink(lesson.link)">
									<i class="now-ui-icons design_bullet-list-67"></i>&nbsp;
									<span v-html="lesson.title"></span></router-link>
							</p>
							<p v-html="data.content"></p>
						</li>
					</ul>

				</card>

				<card class="card-chart d-lg-block d-none" no-footer-line v-loading="loadingStudies">

					<div slot="header">
						<h5 class="card-title">Studies</h5>
					</div>

					<div class="table-responsive">
						<n-table :data="groupData.studies">
							<template slot-scope="{row}">
								<td>
									<router-link :to="'/groups/' + $route.params.slug + $root.cleanLink(row.link)" v-html="row.title"></router-link>
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

			<div class="col-lg-8">

				<el-menu :default-active="defaultActiveTab" class="el-menu-demo" mode="horizontal" :router="true">
					<el-menu-item :index="'/groups/' + this.$route.params.slug + '/'">
						<font-awesome-icon icon="comments"></font-awesome-icon>&nbsp;&nbsp;<span>Discussion</span>
					</el-menu-item>
					<el-menu-item :index="'/groups/' + this.$route.params.slug + '/assignments/'">
						<font-awesome-icon icon="list"></font-awesome-icon>&nbsp;&nbsp;<span>Todos</span></el-menu-item>
					<el-menu-item :index="'/groups/' + this.$route.params.slug + '/studies/'">
						<font-awesome-icon icon="book"></font-awesome-icon>&nbsp;&nbsp;<span>Studies</span>
					</el-menu-item>
					<el-menu-item :index="'/groups/' + this.$route.params.slug + '/members/'">
						<font-awesome-icon icon="user"></font-awesome-icon>&nbsp;&nbsp;<span>Members</span>
					</el-menu-item>
					<el-menu-item :index="'/groups/' + this.$route.params.slug + '/settings/'">
						<font-awesome-icon icon="cogs"></font-awesome-icon>&nbsp;&nbsp;<span>Settings</span>
					</el-menu-item>
				</el-menu>

				<br />

				<router-view :groupData.sync="groupData"></router-view>

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
      groupData     : {
        id         : 0,
        name       : '',
        slug       : '',
        avatar_urls: {
          full : '',
          thumb: ''
        },
        description: {
          rendered: ''
        },
        members    : [],
      },
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
      }
    },
    methods   : {
      setupCurrentGroup () {
        this.$store
          .dispatch('group/fetchGroup', {id: this.$route.params.slug, key: 'slug'})
          .then(() => {
            this.groupData = this.group.group;
            this.getGroupTodos();
            this.getMembers();
          });
      },
      getGroupTodos () {
        this.loadingTodos = true;
        this.$http
          .get(
            '/wp-json/studychurch/v1/assignments?group_id=' + this.groupData.id)
          .then(response => (
            this.todoData = response.data
          ))
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
