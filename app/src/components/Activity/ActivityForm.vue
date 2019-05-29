<template>
	<div :class="getClass">
		<img v-if="getAvatar" class="avatar border-gray" :src="getAvatar">

		<el-input
			ref="commentform"
			type="textarea"
			:autosize="autosize"
			resize="none"
			:disabled="isDisabled"
			:placeholder="placeholder"
			v-model="comment"
			v-loading="loading"
			@change="handleKeydown"
			@blur="submitAnswer"></el-input>
	</div>
</template>
<script>
  import { Input } from 'element-ui';
  import { mapState, mapGetters } from 'vuex';
  import ActivityService from '@/services/ActivityService.js';

  function _interopDefault (ex) {
    return (
      ex && (
        typeof ex === 'object'
      ) && 'default' in ex
    ) ? ex['default'] : ex;
  }

  /* global require */
  let Tribute = _interopDefault(require('tributejs'));

  export default {

    components: {
      Input
    },
    data() {
      return {
        answer        : {
          date   : 0,
          content: {
            raw: ''
          }
        },
        loading       : false,
        tributeOptions: {
          values: []
        },
        tribute       : null,
        comment       : '',
        disable       : false,
      };
    },
    props     : {
      elClass      : [String],
      activityID   : [Number], // the ID of the activity item to post this comment to, leave empty if this is a new Activity item
      avatar       : {
        type   : [String, Boolean],
        default: '',
      },
      component    : {
        type    : [String],
        required: true
      },
      type         : {
        type    : [String],
        required: true
      },
      primaryItem  : {
        type    : [Number, String],
        required: true
      },
      secondaryItem: {
        type: [Number, String]
      },
      placeholder  : {
        type   : [String],
        default: 'Post a reply'
      },
      disabled     : {
        type   : [Boolean],
        default: false
      },
      autosize     : {
        type   : [Boolean, Object],
        default: function () {
          return {minRows: 1}
        }
      }
    },
    computed  : {
      ...mapState(['user', 'group']),
      ...mapGetters('group', ['getGroupMembers']),
      ...mapGetters('user', ['getName', 'getUsername']),
      isDisabled() {
        return this.disabled || this.disable;
      },
      getAvatar() {
        return (
          '' === this.avatar
        ) ? this.user.me.avatar_urls.full : this.avatar;
      },
      getClass() {
        return 'sc-activity--input ' + this.elClass;
      },
      currentGroup() {
        return this.group.group;
      },
      getUsers() {
        return this.user.users;
      },
    },

    watch: {
      currentGroup () {
        this.tribute.append(0, this.getTributeValues(), true);
      },
      getUsers () {
        this.tribute.append(0, this.getTributeValues(), true);
      },
    },

    methods: {
      /**
       * Submit when the user hits the Enter key
       * @param event
       */
      handleKeydown(event) {

        // if the name search is open, don't do anything.
        if (this.tribute.isActive) {
          return;
        }

        // on Enter
        if (event.keyCode === 13 && !event.shiftKey) {
          event.preventDefault();
          this.submitAnswer();
        }

        // on ESC
        if (27 === event.keyCode) {
          event.preventDefault();
          this.$emit('activityCanceled');
        }

      },
      /**
       * handle the answer submit
       */
      submitAnswer() {

        if (!this.comment || this.disable) {
          return;
        }

        this.loading = this.disable = true;

        ActivityService.addActivity({
          id                   : this.activityID,
          component            : this.component,
          type                 : this.type,
          user                 : this.user.me.id,
          prime_association    : this.primaryItem,
          secondary_association: this.secondaryItem,
          content              : this.comment,
          hidden               : true,
        })
          .then(response => {
            this.comment = '';

            if (response.data.length) {
              this.$emit('activitySaved', response.data[0])
            }
          })
          .finally(() => {
            this.loading = this.disable = false;
            this.$root.reftag();
          });
      },
      getTributeValues() {
        let members = this.getGroupMembers;
        let values = [];

        for (let i = 0; i < members.length; i++) {
          if (this.user.me.id === members[i] || !this.getName(members[i])) {
            continue;
          }

          values.push({
            key  : this.getName(members[i]),
            value: this.getUsername(members[i])
          })
        }

        return values;
      },
      setFocus() {
        this.$nextTick(() => {
          this.$refs.commentform.$refs.textarea.focus();
        })
      },
      updateComment(comment) {
        this.comment = this.stripHTML( comment );
      },

		stripHTML( value ) {
            var div = document.createElement("div");
            div.innerHTML = value;
            var text = div.textContent || div.innerText || "";
            return text;
		},
    },
    mounted() {
      let textarea = this.$refs.commentform.$refs.textarea;
      this.tributeOptions.values = this.getTributeValues();

      this.tribute = new Tribute(this.tributeOptions);
      this.tribute.attach(textarea);

      textarea.addEventListener('keydown', this.handleKeydown);
    },
    beforeDestroy() {
      let textarea = this.$refs.commentform.$refs.textarea;
      textarea.removeEventListener('keydown', this.handleKeydown);
    }
  };
</script>
