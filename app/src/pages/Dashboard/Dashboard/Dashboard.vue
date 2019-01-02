<template>
	<div class="sc-dashboard">

		<div class="row">
			<div class="col-lg-4">
				<card class="card-user">
					<div slot="image" class="image">
						<img src="@/assets/img/bg-bible.jpg" alt="...">
					</div>
					<div>
						<div class="author">
							<img class="avatar border-gray" :src="user.me.avatar_urls.full" alt="...">
							<h5 class="title">{{user.me.name}}</h5>
							<p class="description">
								@{{user.me.user_login}}
							</p>
						</div>
					</div>
				</card>
			</div>

			<div class="col-lg-8">

				<el-menu :default-active="this.$route.path" class="el-menu-demo" mode="horizontal" :router="true">
					<el-menu-item :index="'/'">
						<font-awesome-icon icon="users"></font-awesome-icon>&nbsp;&nbsp;<span>Groups</span>
					</el-menu-item>
					<el-menu-item :index="'/assignments/'">
						<font-awesome-icon icon="list"></font-awesome-icon>&nbsp;&nbsp;<span>Todos</span></el-menu-item>
					<el-menu-item :index="'/studies/'">
						<font-awesome-icon icon="book"></font-awesome-icon>&nbsp;&nbsp;<span>Studies</span>
					</el-menu-item>
					<el-menu-item v-if="false" :index="'/notifications/'">
						<font-awesome-icon icon="bell"></font-awesome-icon>&nbsp;&nbsp;<span>Notifications</span>
					</el-menu-item>
					<el-menu-item :index="'/settings/'">
						<font-awesome-icon icon="cogs"></font-awesome-icon>&nbsp;&nbsp;<span>Settings</span>
					</el-menu-item>
				</el-menu>

				<br />

				<router-view></router-view>

			</div>
		</div>

	</div>
</template>
<script>

  import { mapState } from 'vuex'

  import {
    Card
  } from 'src/components'

  import { Menu, MenuItem } from 'element-ui';

  export default {
    components: {
      'el-menu'     : Menu,
      'el-menu-item': MenuItem,
      Card
    },
    computed  : {
      ...mapState(['user'])
	},
    methods   : {}
  }
</script>
<style>
</style>
