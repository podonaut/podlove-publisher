<?php
namespace Podlove\Modules\PodcastIndex;
use \Podlove\Model;

class Podcast_Index extends \Podlove\Modules\Base {

    protected $module_name = 'Podcast Index';
    protected $module_description = 'Podcast Index Namespace Enhancements';
    protected $module_group = 'metadata';
    public function load() {
        $this->register_option('pi_locked', 'radio', [
            'label' => __('Locked', 'podlove-podcasting-plugin-for-wordpress'),
            'description' => '<p>'.__('This tells other podcast platforms whether they are allowed to import this feed.', 'podlove-podcasting-plugin-for-wordpress').'</p>',
            'default' => '0',
            'options' => [
                1 => __('Enabled', 'podlove-podcasting-plugin-for-wordpress'),
                0 => __('Disabled', 'podlove-podcasting-plugin-for-wordpress'),
            ],
        ]);
        add_action('podlove_append_to_feed_head', [$this, 'add_pi_locked_to_feed'], 10, 4);
        add_action('podlove_append_to_feed_entry', [$this, 'add_pi_person_to_feed'], 10, 4);
        add_action('podlove_append_to_feed_head', [$this, 'add_pi_value_to_feed'], 10, 4);
    }

	// <podcast:locked owner="[podcast owner email address]">[yes or no]</podcast:locked>
	public function add_pi_locked_to_feed($podcast)
    {
	    $tag = "locked";
		$pi_locked = $this->get_module_option( 'pi_locked' );
	    if ($pi_locked)
            echo sprintf("\n\t\t<podcast:locked owner=\"%s\">yes</podcast:locked>", $podcast->owner_email);
        else
            echo sprintf("\n\t\t<podcast:locked>no</podcast:locked>");
    }

	// <podcast:person role="[host or guest]" img="[(uri of content)]" href="[(uri to website/wiki/blog)]">[name of person]</podcast:person>
	public function add_pi_person_to_feed($podcast, $feed, $format)
    {
		$pi_locked = $this->get_module_option( 'pi_locked' );
		$host = "Dave Keeshan";
	    if ($pi_locked)
            echo sprintf("\n\t\t<podcast:person role=\"\" img=\"\" href=\"\">%s</podcast:person>", $host);
    }

	// <podcast:value type="[lightning]" method="[keysend]" suggested="[number of bitcoin(float)]">[one or more "valueRecipient" elements]</podcast:value>
	public function add_pi_value_to_feed()
    {
		$pi_locked = $this->get_module_option( 'pi_locked' );
	    if ($pi_locked)
            echo sprintf("\n\t\t<podcast:value type=\"\" method=\"\" suggested=\"\">yes</podcast:value>");
    }

}

// <podcast:transcript url="[url to a file or website]" type="[mime type]" rel="captions" language="[language code]" />
// <podcast:funding url="[url for the show at the platform]">[user provided content to link]</podcast:funding>
// <podcast:chapters url="[url to chapter data file]" type="[mime type]" />
// <podcast:person role="[host or guest]" img="[(uri of content)]" href="[(uri to website/wiki/blog)]">[name of person]</podcast:person>
// <podcast:soundbite startTime="[123]" duration="[30]">[Title of Soundbite]</podcast:soundbite>

// <podcast:location country="[Country Code]" locality="[Locality]" latlon="[latitude,longitude]" (osmid="[OSM type][OSM id]") />
// <podcast:social platform="[service slug]" url="[link to social media account]">[social media handle]</podcast:social>
// <podcast:category>[category Name]</podcast:category>
// <podcast:contentRating>[rating letter]</podcast:contentRating>
// <podcast:previousUrl>[url this feed was imported from]</podcast:previousUrl>
// <podcast:alternateEnclosure url="[url of media asset]" type="[mime type]" length="[(int)]" bitrate="[(float)]" title="[(string)]" rel="[(string)]" />
// <podcast:indexers> + <podcast:block>[domain, bot or service slug]</podcast:block>
// <podcast:images srcset="[url to image] [pixelwidth(int)]w, [url to image] [pixelwidth(int)]w, [url to image] [pixelwidth(int)]w, [url to image] [pixelwidth(int)]w" />
// <podcast:id platform="[service slug]" id="[platform id]" url="[link to the podcast page on the service]" />
// <podcast:contact type="[feedback or advertising or abuse]">[email address or url]</podcast:contact>
// <podcast:value type="[lightning]" method="[keysend]" suggested="[number of bitcoin(float)]">[one or more "valueRecipient" elements]</podcast:value>
// <podcast:valueRecipient name="[name of recipient(string)]" type="[node]" address="[public key of bitcoin/lightning node(string)]" split="[percentage(int)]" />