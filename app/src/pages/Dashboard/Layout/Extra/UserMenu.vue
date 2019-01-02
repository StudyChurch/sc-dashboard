<template>
  <div class="user">
    <div class="photo">
      <img :src="user.me.avatar_urls.full" alt="avatar"/>
    </div>
    <div class="info">
      <a data-toggle="collapse" :aria-expanded="!isClosed" @click.prevent="toggleMenu" href="#">
           <span>
             {{user.me.name}}
             <b class="caret"></b>
          </span>
      </a>
      <div class="clearfix"></div>
      <div>
        <collapse-transition>
          <ul class="nav" v-show="!isClosed">
            <slot>
              <li>
                <router-link to="/">
                  <span class="sidebar-mini-icon">DB</span>
                  <span class="sidebar-normal">My Dashboard</span>
                </router-link>
              </li>
              <li>
                <router-link to="/settings">
                  <span class="sidebar-mini-icon">S</span>
                  <span class="sidebar-normal">My Settings</span>
                </router-link>
              </li>
              <li>
                <a href="/log-out">
                  <span class="sidebar-mini-icon">LO</span>
                  <span class="sidebar-normal">Log Out</span>
                </a>
              </li>
            </slot>
          </ul>
        </collapse-transition>
      </div>
    </div>
  </div>
</template>
<script>
  import { CollapseTransition } from 'vue2-transitions';
  import { getUserData } from 'src/auth';
  import { mapState } from 'vuex';

  export default {
    components: {
      CollapseTransition
    },
    data() {
      return {
        isClosed: true,
      }
    },
    computed: {
      ...mapState(['user'])
    },
    methods: {
      toggleMenu() {
        this.isClosed = !this.isClosed
      }
    }
  }
</script>
<style>
  .collapsed {
    transition: opacity 1s;
  }
</style>
