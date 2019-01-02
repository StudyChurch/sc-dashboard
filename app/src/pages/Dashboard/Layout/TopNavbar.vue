<template>
	<navbar :show-navbar="showNavbar">
		<div class="navbar-wrapper">
			<div class="navbar-toggle" :class="{toggled: $sidebar.showSidebar}">
				<navbar-toggle-button @click.native="toggleSidebar">
				</navbar-toggle-button>
			</div>
			<a class="navbar-brand" href="#pablo">
				{{$route.name}}
			</a>
		</div>
		<button @click="toggleNavbar" class="navbar-toggler" type="button" data-toggle="collapse"
				data-target="#navigation"
				aria-controls="navigation-index" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-bar navbar-kebab"></span>
			<span class="navbar-toggler-bar navbar-kebab"></span>
			<span class="navbar-toggler-bar navbar-kebab"></span>
		</button>

		<template slot="navbar-menu">

			<ul class="navbar-nav">
				<li class="nav-item">
					<a href="/library" class="nav-link">Library</a>
				</li>
				<li class="nav-item">
					<a href="/account" class="nav-link">Account</a>
				</li>
				<li class="nav-item">
					<a href="/" class="nav-link">Dashboard</a>
				</li>
			</ul>

		</template>
	</navbar>
</template>
<script>
  import { RouteBreadCrumb, Navbar, NavbarToggleButton } from 'src/components';
  import { CollapseTransition } from 'vue2-transitions'

  export default {
    components: {
      RouteBreadCrumb,
      Navbar,
      NavbarToggleButton,
      CollapseTransition
    },
    computed  : {
      routeName() {
        const {name} = this.$route
        return this.capitalizeFirstLetter(name)
      }
    },
    data() {
      return {
        activeNotifications: false,
        showNavbar         : false
      }
    },
    methods   : {
      capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1)
      },
      toggleNotificationDropDown() {
        this.activeNotifications = !this.activeNotifications
      },
      closeDropDown() {
        this.activeNotifications = false
      },
      toggleSidebar() {
        this.$sidebar.displaySidebar(!this.$sidebar.showSidebar)
      },
      toggleNavbar() {
        this.showNavbar = !this.showNavbar;
      },
      hideSidebar() {
        this.$sidebar.displaySidebar(false)
      }
    }
  }

</script>
<style>

</style>
