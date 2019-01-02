<template>
	<div :class="getClass">
		<img v-if="getAvatar" class="avatar border-gray" :src="getAvatar">

		<el-input
			ref="commentform"
			type="textarea"
			:autosize="autosize"
			resize="none"
			:disabled="disabled"
			:placeholder="placeholder"
			v-model="comment"
			v-loading="loading"
			@change="handleKeydown"
			@blur="handleKeydown"></el-input>
	</div>
</template>
<script>
  import { Input } from 'element-ui';
  import { mapState } from 'vuex';

  function _interopDefault (ex) {
    return (
      ex && (
        typeof ex === 'object'
      ) && 'default' in ex
    ) ? ex['default'] : ex;
  }

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
          values: this.getTributeValues()
        },
        tribute       : null,
        comment       : ''
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
      getAvatar() {
        return (
        '' === this.avatar
        ) ? this.user.me.avatar_urls.full : this.avatar;
      },
      getClass() {
        return 'sc-activity--input ' + this.elClass;
      }
    },

    watch: {
      '$root.currentGroup' (to, from) {
        this.tribute.append(0, this.getTributeValues(), true);
      }
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
        this.loading = true;

        this.$http
          .post('/wp-json/studychurch/v1/activity/', {
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
          .finally(() => this.loading = false)
      },
      getTributeValues() {
        let group = this.$root.getCurrentGroupData();
        let values = [];

        if (!group) {
          return [];
        }

        for (let i = 0; i < group.members.length; i++) {
          values.push({
            key  : group.members[i].name,
            value: group.members[i].username
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
        this.comment = comment;
      }
    },
    mounted() {
      let _this = this;
      let textarea = this.$refs.commentform.$refs.textarea;

      this.tribute = new Tribute(this.tributeOptions);
      this.tribute.attach(textarea);

//      textarea.addEventListener('tribute-replaced', function (e) {
//        _this.$emit('tribute-replaced', e);
//      });
//
//      textarea.addEventListener('tribute-no-match', function (e) {
//        _this.$emit('tribute-no-match', e);
//      });

      textarea.addEventListener('keydown', this.handleKeydown);
    },
    beforeDestroy() {
      let textarea = this.$refs.commentform.$refs.textarea;
      textarea.removeEventListener('keydown', this.handleKeydown);
    }
  };
</script>
