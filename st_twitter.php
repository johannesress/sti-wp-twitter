<?php
error_reporting(E_ALL);
/**
 * Plugin Name: ST Widgets - Twitter
 * Plugin URI: http://stinformatik.eu
 * Description: Displays latest Tweets from a given Twitter User
 * Version: 0.5
 * Author: Johannes Reß
 * Author URI: http://johannesress.com
 * License: No licensing
 */

require_once 'twitteroauth-class/twitteroauth.php';

class ST_Twitter extends WP_Widget {

	function __construct() {
		$params = array(
			'description' => 'Anzeigen und abspeichern der letzten Tweets eines Twitter Users.',
			'classname' => 'st-twitter-widget',
			'name' => 'Twitter Feed'
		);

		parent::__construct('ST_Twitter', '', $params);
	}

	public function form($instance) {
		extract($instance);
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Titel</label>
			<input type="text"
				class="widefat" 
				id="<?php echo $this->get_field_id('title'); ?>"
				name="<?php echo $this->get_field_name('title'); ?>"
				value="<?php if(isset($title)) echo esc_attr($title); ?>" />
		</p>
		<p>
			<small>Änderungen an diesem Widget sind ggf. erst nach ca. 10min sichtbar.</small>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('twitter_username'); ?>">Twitter Username</label>
			<input type="text"
				class="widefat" 
				id="<?php echo $this->get_field_id('twitter_username'); ?>"
				name="<?php echo $this->get_field_name('twitter_username'); ?>"
				value="<?php if(isset($twitter_username)) echo esc_attr($twitter_username); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('twitter_tweetcounter'); ?>">Anzahl Tweets</label>
			<input type="number"
				class="widefat"
				min="1"
				max="10"
				id="<?php echo $this->get_field_id('twitter_tweetcounter'); ?>"
				name="<?php echo $this->get_field_name('twitter_tweetcounter'); ?>"
				value="<?php echo !empty($twitter_tweetcounter) ? esc_attr($twitter_tweetcounter) : '5'; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('twitter_button'); ?>">
				<input type="checkbox" 
					id="<?php echo $this->get_field_id('twitter_button'); ?>"
					name="<?php echo $this->get_field_name('twitter_button'); ?>" 
					<?php if(isset($twitter_button)) : ?>
						<?php echo ($twitter_button) ? "checked" : ""; ?>
					<?php endif; ?>
					/>
					"Folgen" Button anzeigen?
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('twitter_button_text'); ?>">Text im "Folgen" Button</label>
			<input type="text"
				class="widefat"
				id="<?php echo $this->get_field_id('twitter_button_text'); ?>"
				name="<?php echo $this->get_field_name('twitter_button_text'); ?>"
				value="<?php echo !empty($twitter_button_text) ? esc_attr($twitter_button_text) : 'Folge mir!'; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('twitter_dateformat'); ?>">strftime Datumsformat (z.B. %d.%m.%G oder %A der %d. %B %G)</label>
			<input type="text"
				class="widefat"
				id="<?php echo $this->get_field_id('twitter_dateformat'); ?>"
				name="<?php echo $this->get_field_name('twitter_dateformat'); ?>"
				value="<?php echo !empty($twitter_dateformat) ? esc_attr($twitter_dateformat) : '%d.%m.%G'; ?>" />
		</p>
		<?php
	}

	public function widget($args, $instance) {
		extract($args);
		extract($instance);

		if( empty($title) ) $title = "Twitter";
		$title = apply_filters('widget_title', $title);
		$twitter_username = apply_filters('widget_twitter_username', $twitter_username);

		if( empty($twitter_tweetcounter) ) $twitter_tweetcounter = 5;

		if( empty($twitter_dateformat) ) $twitter_dateformat = '%d.%m.%G';

		if( empty($twitter_button_text) ) $twitter_button_text = 'Folge mir!';

		if( empty($twitter_username) ) $twitter_username = 'johannesress';

		$data = $this->twitter($twitter_username , $twitter_tweetcounter);

		echo $before_widget . "<div class='st-twitter-widget'>" ;
			echo $before_title . $title . $after_title;

			foreach($data as $tweet) {
				setlocale(LC_ALL, 'de_DE');
				$date = strftime($twitter_dateformat, strtotime($tweet->created_at));

				$new_text = preg_replace("/[[:alpha:]]+:\/\/[^<>[:space:]]+[[:alnum:]\/]/","<a href=\"\\0\">\\0</a>", $tweet->text);
				echo "<li class='st-tweet'><span class='st-tweet-date'>" . $date . "</span><p>" . $new_text . "</p></li>";
			}

			if($twitter_button)
				echo "<a class='st-twitter-button' href='http://twitter.com/".$twitter_username."' target='_blank'>" .$twitter_button_text. "</a>";

		echo "</div>".$after_widget;
	}

	private function twitter($username, $count) {
		if(empty($username)) return false;

		return $this->fetch_tweets($username, $count);
		
	}

	private function fetch_tweets($username, $count) {

		$transName = 'list_tweets';
		$cacheTime = 10;

		if(false === ($twitterData = get_transient($transName) ) ) {
		     // require the twitter auth class
		     
		    $twitterConnection = new TwitterOAuth(
				'vMJeSulWj0R3aOtjlvkrHA',	// Consumer Key
				'QfZuR5FrjSew6xZ3KlZ6bcfWznx7uGc2peE2yTk4R4',   	// Consumer secret
				'91338022-xsp04e9AL8YmpphDefwyxcuKg78cwOcDOxuBNR9rc',       // Access token
				'K83TMQWkHfwxbRAW7kyCdQ7OFl1KaqBGAicPkFwtrwsTx'    	// Access token secret
			);

		    $twitterData = $twitterConnection->get(
				'statuses/user_timeline',
				array(
					'screen_name'     => $username,
					'count'           => $count,
					'exclude_replies' => false
				)
			);

		    if($twitterConnection->http_code != 200) {
		        $twitterData = get_transient($transName);
		    }

		    // Save our new transient.
		    set_transient($transName, $twitterData, 60 * $cacheTime);
		}

		return get_transient('list_tweets');
	}

}

add_action('widgets_init', 'jr_register_st_twitter');

function jr_register_st_twitter() {
	register_widget('ST_Twitter');
}