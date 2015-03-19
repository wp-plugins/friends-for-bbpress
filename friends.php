<?php

	/**
	 * Plugin Name: Friends For bbPress
	 * Description: Add friends in bbPress
	 * Version: 1.0
	 * Author: Graeme Scott
	 */
	
	function update_online_users_status(){
		
		if(is_user_logged_in()){

			if(($logged_in_users = get_transient('users_online')) === false) $logged_in_users = array();
			
			$current_user = wp_get_current_user();
			$current_user = $current_user->ID;  
			$current_time = current_time('timestamp');
			
			if(!isset($logged_in_users[$current_user]) || ($logged_in_users[$current_user] < ($current_time - (15 * 60)))){
				$logged_in_users[$current_user] = $current_time;
				set_transient('users_online', $logged_in_users, 30 * 60);
			}
		}
	}
	
	add_action('wp', 'update_online_users_status');
	
	function bbpress_friends()	{
		$current_user = wp_get_current_user();
		$friends = get_users();

		$request = '';
		$cancel = '';
		$accept = '';
		$decline = '';
		$remove = '';
		$friend_id = '';

		if(isset($_POST['request'])){ $request = sanitize_text_field($_POST['request']); }
		if(isset($_POST['cancel'])){ $cancel = sanitize_text_field($_POST['cancel']); }
		if(isset($_POST['accept'])){ $accept = sanitize_text_field($_POST['accept']); }
		if(isset($_POST['decline'])){ $decline = sanitize_text_field($_POST['decline']); }
		if(isset($_POST['remove'])){ $remove = sanitize_text_field($_POST['remove']); }
		if(isset($_POST['id'])){ $friend_id = sanitize_text_field($_POST['id']); }
		
		$friendsList = get_user_meta( $current_user->ID, 'friends_list', true);
		$currentFriendsList = get_user_meta( $friend_id, 'friends_list', true);
		$requestList = get_user_meta( $current_user->ID, 'request_list', true);
		
		if ($request) {
			if(empty($requestList)) {
				$newList = array($friend_id);
				add_user_meta( $current_user->ID, 'request_list', $newList);
			} else {
				array_unshift($requestList, $friend_id);
				update_user_meta( $current_user->ID, 'request_list', $requestList);
			}	
		}
		
		if ($cancel) {
			if(($redundant_friend = array_search($friend_id, $requestList)) !== false) {
	            unset($requestList[$redundant_friend]);
	        }
	        
	        update_user_meta( $current_user->ID, 'request_list', $requestList);
	        
	        if(count($requestList) == 0) {
		        delete_user_meta( $current_user->ID, 'request_list');
	        }
		}
		
		if ($accept) {
			$requestList = get_user_meta( $friend_id, 'request_list', true);
			
			if(empty($friendsList)) {
				$newList = array($friend_id);
				add_user_meta( $current_user->ID, 'friends_list', $newList);
			} else {
				array_unshift($friendsList, $friend_id);
				update_user_meta( $current_user->ID, 'friends_list', $friendsList);
			}
			
			if(empty($currentFriendsList)) {
				$newList = array($current_user->ID);
				add_user_meta( $friend_id, 'friends_list', $newList);
			} else {
				array_unshift($currentFriendsList, $current_user->ID);
				update_user_meta( $friend_id, 'friends_list', $currentFriendsList);
			}
			
			if(($redundant_friend = array_search($current_user->ID, $requestList)) !== false) {
	            unset($requestList[$redundant_friend]);
	        }
	        
	        update_user_meta( $friend_id, 'request_list', $requestList);
	        
	        if(count($requestList) == 0) {
		        delete_user_meta( $friend_id, 'request_list');
	        }
		}
		
		if ($decline) {
			$requestList = get_user_meta( $friend_id, 'request_list', true);
		
			if(($redundant_friend = array_search($current_user->ID, $requestList)) !== false) {
	            unset($requestList[$redundant_friend]);
	        }
	        
	        update_user_meta( $friend_id, 'request_list', $requestList);
	        
	        if(count($requestList) == 0) {
		        delete_user_meta( $friend_id, 'request_list');
	        }
		}
		
		if ($remove) {
			if(($redundant_friend = array_search($friend_id, $friendsList)) !== false) {
	            unset($friendsList[$redundant_friend]);
	        }
	        
	        update_user_meta( $current_user->ID, 'friends_list', $friendsList);
	        
	        if(count($friendsList) == 0) {
		        delete_user_meta( $current_user->ID, 'friends_list');
	        }
	        
			if(($redundant_friend = array_search($current_user->ID, $currentFriendsList)) !== false) {
	            unset($currentFriendsList[$redundant_friend]);
	        }
	        
	        update_user_meta( $friend_id, 'friends_list', $currentFriendsList);
	        
	        if(count($currentFriendsList) == 0) {
		        delete_user_meta( $friend_id, 'friends_list');
	        }
		} 
		
		update_user_meta( $current_user->ID, 'friends_list', $friendsList);
		
		$friendsList = get_user_meta( $current_user->ID, 'friends_list', true);
		$requestList = get_user_meta( $current_user->ID, 'request_list', true);
		
		$user_page = bbp_get_user_id(0, true, false);
		
		if (is_user_logged_in()) { 
		
			if($user_page != $current_user->ID) {
				echo '<form method="post" action="" style="color: grey; font-weight: bold;">';
					echo '<input name="id" type="hidden" value="' . $user_page . '">';
					
					if (in_array($user_page, $requestList)) {
						echo 'Friendship Pending Approval';
						echo '<br>';
						echo '<input name="cancel" type="submit" id="action_submit_inbox" value="Cancel Request">';
						echo '<br>';
						echo '<br>';
					} elseif (!in_array($user_page, $friendsList)) {
						echo '<input name="request" type="submit" id="action_submit_inbox" value="Add Friend">';
						echo '<br>';
						echo '<br>';
					} else {
						echo '<input name="remove" type="submit" id="action_submit_inbox" value="Remove Friend">';
						echo '<br>';
						echo '<br>';
					}
					
				echo '</form>';
			}
		
			$friendsList = get_user_meta( $user_page, 'friends_list', true);

			if (!$friendsList) {
				$friendsList = array();
			}
		
			if(in_array($current_user->ID, $friendsList) || $user_page == $current_user->ID) {
				echo '<h2 class="entry-title">Friends</h2>';
				
				$online = array();
				$offline = array();
				
				function is_user_online($user_id) {
				  $logged_in_users = get_transient('users_online');
				  return isset($logged_in_users[$user_id]) && ($logged_in_users[$user_id] > (current_time('timestamp') - (15 * 60)));
				}
				
				if($friendsList) {
					
					foreach($friendsList as $friend) {
						$friend = get_userdata($friend);
						if (is_user_online($friend->ID)) {
							$online[] = $friend;
						} else {
							$offline[] = $friend;
						}
					}
				
					if($online) {
						echo '<p>Online:</p>';
						foreach($online as $friend) {
							if (is_user_online($friend->ID)) {
								echo '<div style="display: inline-block; padding-right: 10px;" align="center">';
									echo '<a href="' . bbp_get_user_profile_url( $friend->ID ) . '">' . get_avatar( $friend->ID, '48' ) . '</a>';
									echo '<br>';
									echo '<a href="' . bbp_get_user_profile_url( $friend->ID ) . '">' . $friend->user_login . '</a>';
									echo ' <div style="display: inline-block; background: limegreen; border-radius: 50%; width: 10px; height: 10px;"></div>';
									echo '<br>';
								echo '</div>';
							} else {
								$offline[] = $friend;
							}
						}
						echo '<br>';
						echo '<br>';
					}
					
					if($offline) {
						echo '<p>Offline:</p>';
						
						foreach($offline as $friend) {
							echo '<div style="display: inline-block; padding-right: 10px;" align="center">';
								echo '<a href="' . bbp_get_user_profile_url( $friend->ID ) . '">' . get_avatar( $friend->ID, '48' ) . '</a>';
								echo '<br>';
								echo '<a href="' . bbp_get_user_profile_url( $friend->ID ) . '">' . $friend->user_login . '</a>';
								echo ' <div style="display: inline-block; background: red; border-radius: 50%; width: 10px; height: 10px;"></div>';
								echo '<br>';
							echo '</div>';
						}
						echo '<br>';
						echo '<br>';
					}
				} else {
					echo 'You have not added any friends yet';
				}
			}
				
			if($user_page == $current_user->ID) {
				$friendRequests = array();
				
				foreach($friends as $friend) {
					$requestList = get_user_meta( $friend->ID, 'request_list', true);
					if ($requestList) {
						if (in_array($current_user->ID, $requestList)) {
							$friendRequests[] = $friend;
						}
					}
				}
			
				if($friendRequests) {
					echo '<br>';
					echo '<br>';
					echo '<p>Friend Requests:</p>';
					
					foreach($friendRequests as $friend) {
						echo '<form method="post" action="">';
							echo '<div style="display: inline-block; padding-right: 10px;" align="center">';
								echo '<a href="' . bbp_get_user_profile_url( $friend->ID ) . '">' . get_avatar( $friend->ID, '48' ) . '</a>';
								echo '<br>';
								echo '<a href="' . bbp_get_user_profile_url( $friend->ID ) . '">' . $friend->user_login . '</a>';
								echo '<br>';
								echo '<input name="id" type="hidden" value="' . $friend->ID . '">';
								echo '<input name="accept" type="submit" id="action_submit_inbox" value="Accept">';
								echo '<input name="decline" type="submit" id="action_submit_inbox" value="Decline">';
							echo '</div>';
						echo '</form>';
					}
				}
			}
		}
	}
	
	add_action( 'bbp_template_after_user_profile', 'bbpress_friends' );

?>