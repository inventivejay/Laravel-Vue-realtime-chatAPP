<template>
<div id="main-compo">
<div id="frame">
	<div id="sidepanel">
		<div id="profile">
			<div class="wrap">
				<img id="profile-img" src="http://emilcarlsson.se/assets/mikeross.png" class="online" alt="" />
			</div>
		</div>
		<div id="search">
			<label for=""><i class="fa fa-search" aria-hidden="true"></i></label>
			<input type="text" placeholder="Search contacts..." />
		</div>
		<div id="contacts">
			<ul>
				<li class="contact" @click.prevent="handleSelectClick(user)" v-for="user in userList" :key="user.id">
					<div class="wrap">
						<span :class="`contact-status ${onlineUser(user.id) || online.id==user.id   ? 'online' : ''}`"></span>
						<img src="http://emilcarlsson.se/assets/louislitt.png" alt="" />
						<div class="meta">
              <div class="meta-sep">
							<p class="name-style">{{user.name}}</p>
              <p class="name-typing" v-if="typing && typing == user.name" >typing...</p>
              <p class="badge badge-pill badge-danger count-badge" v-if="user.unread">{{user.unread}}</p>
              </div>
						</div>
					</div>
				</li>

			</ul>
		</div>
		<div id="bottom-bar">
			<button id="addcontact"><i class="fa fa-user-plus fa-fw" aria-hidden="true"></i> <span>Add contact</span></button>
			<button id="settings"><i class="fa fa-cog fa-fw" aria-hidden="true"></i> <span>Settings</span></button>
		</div>
	</div>
	<div class="content">
		<div v-if="userMessage.user" class="contact-profile">
			<img src="http://emilcarlsson.se/assets/harveyspecter.png" alt="" />
      <p class="m-b-0">{{userMessage.user.name}}</p>
			<div class="social-media">
				<i class="fa fa-facebook" aria-hidden="true"></i>
				<i class="fa fa-twitter" aria-hidden="true"></i>
				 <i class="fa fa-instagram" aria-hidden="true"></i>
			</div>
		</div>
		<div class="messages">
			<ul>
				<li v-for="message in userMessage.messages" :key="message.id" :class="`${message.user.id == userMessage.user.id ? 'sent' : 'replies'}`">
					<img src="http://emilcarlsson.se/assets/mikeross.png" alt="" />
					<p>{{message.message}}</p>
				</li>
			</ul>
		</div>
		<div v-if="userMessage.user" class="message-input">
			<div class="wrap">
			<input v-if="userMessage.user" @keydown="typeingEvent(userMessage.user.id)" @keydown.enter="sendMessage" v-model="message" name="message-to-send" id="message-to-send" placeholder="Write your message..."/>
			<i class="fa fa-paperclip attachment" aria-hidden="true"></i>
			<button  @click.prevent="sendMessage" class="submit"><i class="fa fa-paper-plane" aria-hidden="true"></i></button>
			</div>
		</div>
	</div>
</div>
</div>

</template>

<script>
import _ from 'lodash'
export default {
  mounted(){
    Echo.private(`chat.${authuser.id}`)
    .listen('MessageSend', (e) => {
      this.handleIncoming(e.message);
    });
    this.$store.dispatch('userList');

Echo.private('typingevent')
    .listenForWhisper('typing', (e) => {
      if (this.userMessage.length != 0) {
        if(e.user.id == this.userMessage.user.id || 0 && e.userId == authuser.id){
          this.typing = e.user.name;
        }
          setTimeout(() => {
            this.typing = '';
          }, 2000);
      }
    });
Echo.join('liveuser')
    .here((users) => {
      this.users = users
    })
    .joining((user) => {
        this.online = user
    })
    .leaving((user) => {
        console.log(user.name);
    });

  },
  data(){
    return{
      message:'',
      typing:'' ,
      users:[],
      online:'',
      userUnreadId:''
      }
  },
  computed:{
    userList(){
      return _.sortBy(this.$store.getters.userList, [
          user => {
              return user.unread;
          }
      ]).reverse();
    },
    userMessage(){
      return  this.$store.getters.userMessage
    }
  },
  created(){

  },
  updated(){
    $('.messages').scrollTop($('.messages')[0].scrollHeight);

  },
  methods:{
    selectUser(userId){
      this.$store.dispatch('userMessage',userId);
      this.selectedContact = userId;
    },
    sendMessage(e){
      e.preventDefault();
      if(this.message!=''){
        axios.post('/sendnewmessage',{message:this.message,user_id:this.userMessage.user.id})
        .then(response=>{
          this.selectUser(this.userMessage.user.id);
        })
        this.message = '';
      }
    },
    handleSelectClick(userSelected) {
      this.updateUnreadCount(userSelected, true)
      this.selectUser(userSelected.id);
      axios.get(`/unreadupdate/${userSelected.id}`)
      .then(response=>{
        this.selectUser(this.userMessage.user.id)
      })

    },
    handleIncoming(fromUser) {
    if (this.selectedContact && fromUser.from == this.selectedContact) {
        this.selectUser(fromUser.from);
    }
      this.updateUnreadCount(fromUser, false);
    },
    deleteSingleMessage(messageId){
      axios.get(`/deletesinglemessage/${messageId}`)
      .then(response=>{
        this.selectUser(this.userMessage.user.id)
      })
    },
    deleteAllMessage(){
        axios.get(`/deleteallmessage/${this.userMessage.user.id}`)
      .then(response=>{
        this.selectUser(this.userMessage.user.id)
      })
    },
    typeingEvent(userId){
      Echo.private('typingevent')
      .whisper('typing', {
          'user': authuser,
          'typing':this.message,
          'userId':userId
      });
    },
    onlineUser(userId){
      return _.find(this.users,{'id':userId});
    },
    updateUnreadCount(user, reset) {
      if(!user.from) this.userUnreadId = user.id
        else this.userUnreadId = user.from

      this.userList.map(single => {
        if (single.id != this.userUnreadId)
            return single;

        if (reset)
          single.unread = 0;
            else
            single.unread += 1;
          return single;
      });
    }
  }
}
</script>

<style scoped src="../../../public/css/chat.css">
</style>
