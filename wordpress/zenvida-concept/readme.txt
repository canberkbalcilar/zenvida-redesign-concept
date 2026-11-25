=== ZenVida Concept ===
Contributors: zenvida
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Theme for ZenVida Foot & Body, Madrid. Built so that non-technical editors manage everything in wp-admin.

== Description ==

* Rituals: a simple form per ritual (prices, photos, category, show/hide), drag-and-drop order.
* Pages are built from ZenVida blocks: every text, button, photo and FAQ item is editable.
* Events (workshops, special weeks) and event popups with start and end dates.
* Site settings: contact details, opening hours (including closed days), booking card texts, promo bar, quiz, Google titles.
* Dashboard: a short "what do you want to change?" guide.

== Installation ==

1. Install WordPress, then Appearance → Themes → Add New → Upload Theme → zenvida-concept.zip → Activate.
2. On an empty WordPress the starter content installs by itself on the next admin page: 19 rituals with prices, all pages, policy pages, menus, settings and photos (downloaded into the media library). Sample events, journal posts and the sample popup arrive as drafts.
3. On a WordPress that already has content nothing is changed; an "Install starter content" button appears instead.
4. Site settings: check contact details and opening hours. Install an SMTP plugin so form emails arrive reliably.
5. Updates: new versions appear under Dashboard → Updates like any other theme update and install by themselves (Appearance → Themes → ZenVida Concept → "Disable auto-updates" to update by hand instead). An update only changes the theme files; rituals, pages, prices, photos and settings stay as they are.

== Changelog ==

= 1.17.0 =
* Dropdown menus: items placed under another item in Appearance → Menus (main menu) now open from a small arrow, on hover or with the keyboard on desktop, and under their parent in the phone menu.
* Phone menu: the Book now button text is centred.

= 1.16.0 =
* Siesta Club page, new editorial layout: a full-width photo cover, a large opening statement, Gold and Platinum as magazine spreads with prices set in large type, a details table, a full-width photo, "the first forty" perks, the terms in brief, the FAQ and a "Reserve a place" section with the calculator. Works on phones; Spanish on /es/.
* The page switches to the new layout by itself on update. If the page was edited by hand, it is kept as it is and the new layout is added as a draft page ("Siesta Club, new layout").
* Every section is its own block, editable from the block sidebar; the calculator takes its prices from the Gold and Platinum blocks.

= 1.15.0 =
* New Siesta Club page (/siesta-club/), added automatically and linked in the footer Offers menu: the founding membership with Gold and Platinum plans (6 or 12 months), member benefits, Founding Member perks, how it works, ways to pay, key dates, FAQ, and a "Reserve your place" calculator that shows what is paid today and sends the choice on WhatsApp. In Spanish on /es/.
* Every text, price and list is editable in the page's blocks; prices live in the plans block only. Places left: Appearance → ZenVida settings → Siesta Club.
* The old address /membership now leads to the Siesta Club page.

= 1.14.0 =
* Structured data for Google and AI search on every page, written by the theme from your content: the studio (address, opening hours, phone, WhatsApp, price range, Google rating and the Google reviews shown on the page), breadcrumbs, the rituals as services with their prices, the full price list, packs and memberships, the gift card, events, journal posts and every FAQ. In Spanish on /es/.
* Rank Math (or Yoast) keeps titles, descriptions, Open Graph and the sitemap; its own schema is switched off so Google sees one consistent set. Rituals and events that are switched off stay out of the Rank Math sitemap.
* Site settings → Contact: new "Map coordinates" and "Social profiles" fields for Google.

= 1.13.0 =
* Ritual pages: the Google rating under the ritual name ("4.9 · 600+ Google reviews"), linking to a new reviews section just before the FAQ.
* The section shows the Google reviews that mention the ritual ("What guests say about Reflexology"), with the matching words in bold. With fewer than two such reviews it shows the newest reviews ("What guests say about ZenVida"). Sliding cards, as on the homepage. In Spanish on /es/.

= 1.12.0 =
* New theme versions now arrive within the hour: the theme checks for updates every hour (WordPress itself only checks twice a day) and installs a new version by itself when automatic updates are on.
* Dashboard → Updates always shows the newest version, without waiting.

= 1.11.0 =
* Reviews slide sideways: the newest 10 Google reviews (3 to 20, set in the Reviews block), three at a time on desktop, two on tablets and one on phones, with arrows and swipe. The next card peeks in on phones.
* Cards stay the same height; long reviews show 6 lines, the full text is on Google.

= 1.10.0 =
* Google reviews from ZenVida's own Business Profile. Site settings → Google reviews: paste the OAuth client from Google Cloud, connect the Google account that manages the profile, and choose which reviews to show (5 stars, 4 and 5 stars, or all; always with a written comment, newest first).
* The Reviews block shows the newest Google reviews with an "All reviews on Google" link. The rating and the number of reviews everywhere on the site come from Google ("4.9 from 450+ Google reviews"), in Spanish on /es/.
* Reviewers are shown as first name and last initial. On the Spanish site, reviews written in Spanish show in the guest's own words.
* Updated once a day, or with "Update now". Without a connection the reviews and numbers typed in the editor are shown, as before.

= 1.9.0 =
* ZenVida brand identity (Brand Guidelines, December 2025): the primary logo in the header (black) and footer (ivory), and the isotype as the browser and home-screen icon. A logo uploaded in Customize still takes priority.
* Brand colours only: Black #191919, Ivory #fffef8 and Narvik #e8e4da with their tints. Gold accents and coloured labels are now black, brand greys or Narvik.
* Brand storytelling icons for Sanctuary, Ritual and Culture on the homepage (also on existing sites), plus Herbal spring, Hands massage and Sun in the editor's icon list.
* The brand isotype as a light line texture behind cream sections (flat digital version, 4 KB).
* Ready for the brand fonts: with averta-pe-regular.woff2, averta-pe-semibold.woff2, averta-pe-bold.woff2 and riccione-regular.woff2 in wp-content/uploads/zenvida-fonts/ the site uses Averta PE for text and Riccione for titles.
* Phones: the round WhatsApp button no longer covers the booking widget's Continue button on the Rituals & Prices page.

= 1.8.0 =
* Online booking with ZenVida's own booking widget, the same one as on zenvida.es: live free times, client details and the card deposit. Ritual pages show it opened on that ritual, and the duration buttons switch it (for example 60 or 90 minutes).
* New "Rituals & Prices" page, at the same address as on Wix, with the full booking menu. The header "Book now" button goes there. A "Booking widget" block can be added to any page (full menu, one service, one category or group booking).
* Ritual details, section 5 "Online booking": choose the ritual's service in the booking system. It warns when a duration or a price does not match. Rituals without a service show a "Book on WhatsApp" card.
* Site settings, "Online booking": switch the widget on or off, Google Tag Manager ID (the widget reports bookings to Google Ads, Analytics and Meta), widget addresses.
* Sites updated from an earlier version: the rituals are connected and the page is added automatically.
* The booking system only answers requests from zenvida.es. On another address, such as a test domain, the widget can't load the menu until ZenVida's developer adds that address. The online demo keeps the prototype booking card for this reason.

= 1.7.0 =
* Updates from the dashboard: when a new version is published, Dashboard → Updates and Appearance → Themes show "Update available" with what is new, and one click installs it. Automatic updates are switched on, so new versions also install by themselves within about 12 hours.
* An update only replaces the theme files. Rituals, pages, prices, photos, messages and settings are not changed.

= 1.6.0 =
* Floating WhatsApp button in the bottom-right corner, like on the current website. Site settings → Contact: switch it on or off and set the first message that is already typed for the visitor (in Spanish on /es/).
* On phones it moves above the "Book" bar, or steps aside where that bar already has WhatsApp. Hidden while an offer popup is open, and on ritual pages next to the booking card (which has its own "Ask on WhatsApp").

= 1.5.0 =
* Spanish version with the free TranslatePress plugin: the whole site at /es/, English stays the main language. EN / ES switch in the header goes to the same page in the other language.
* Ready-made Spanish for all starter content (inc/data/es.json, 1,900+ texts): pages, 19 rituals, policies, menus and the 18 journal posts (from the Spanish versions on zenvida.es). Loaded automatically, or with the "Install Spanish translations" button after installing TranslatePress. Texts the client already translated are never overwritten.
* Booking card, quiz, body map, opening hours, forms and WhatsApp messages in Spanish, with Spanish dates (inc/data/es-ui.json). Browser titles and descriptions translated too.
* Messages sent from the Spanish site are marked "Language: Spanish" in the inbox.
* Dashboard guide: "Change a Spanish text" opens TranslatePress's editor on the Spanish site.

= 1.4.0 =
* Journal: the 18 Respiro posts from zenvida.es are now part of the starter content (texts, photos, headings, lists, buttons, tables, questions), with their original dates and addresses. Links to old zenvida.es pages point to the matching new pages. The 3 sample posts are gone.
* Refer a friend: new page with a form (your details + your friend's), saved under Messages and emailed; its own terms page; link in the footer "Offers" menu. Thank-you text in Site settings.

= 1.3.0 =
* Keyboard and screen readers: quiz, popups and booking card work fully with the keyboard (arrow keys for durations, foot bath and days; focus kept on the chosen time; Esc closes and returns focus). Skip link, visible focus rings everywhere, mobile menu closes with Esc.
* Duplicate: copy a ritual, event or popup as a draft from the list (or from the ritual form).
* Rituals → Change prices: raise or lower prices for all rituals, one area or picked ones, by % or €, with rounding. Apply now or from a date (00:00 Madrid). Live preview, cancel scheduled changes, undo applied ones.

= 1.2.0 =
* How to find us merged into the Contact page (Getting here section, #find-us); I have a gift card page removed. Old addresses redirect.

= 1.1.0 =
* Body map block: tap an area of the body to see the rituals that help there ("Where it helps" per ritual).
* How to find us page: route steps with photos, Google / Apple Maps, copy address, show-to-taxi card.
* I have a gift card page: how to use it, book on WhatsApp, questions form with a gift card code field.

= 1.0.0 =
* Starter content on real installs: automatic on an empty WordPress, button otherwise; photos imported into the media library; demo mode off; samples as drafts.
* Fix: foot-bath list and quiz questions were emptied when content was saved from the admin.

= 0.9.0 =
* Work with us page: job cards and application form with private CV upload.
* Customer information and legal pages: Reservations Policy, Spa Rules, Code of Conduct, Terms of Use, Privacy and Cookies Policies, Group Appointments.
* Booking card follows the Reservations Policy: deposit EUR 25, or EUR 45 from EUR 90; groups arranged by reception.

= 0.8.0 =
* Offers & Packs and Gift Cards pages: pricing cards, gift card builder with live preview.
* Contact form; form messages and gift card requests saved under Messages and emailed.
* Responsive photos (srcset/sizes) and media-library alt text on every image.

= 0.7.0 =
* Closed days in opening hours, clearer hours validation.
* Works with Yoast SEO and the Classic Editor plugin; search and 404 pages.
* Security and robustness fixes from a full review.
