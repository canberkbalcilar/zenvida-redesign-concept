<?php
/**
 * Demo content: customer information and legal pages, Group appointments, Work with us.
 * Used by zv_seed() only. Policy texts restate ZenVida's published policies (zenvida.es,
 * updated 1 June 2026) for the new site; items in [square brackets] need the client's input,
 * and the legal pages should be reviewed by the client's adviser before launch.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- core block helpers ---------- */

function zv_h( $text, $level = 2 ) {
	$html = '<h' . $level . ' class="wp-block-heading">' . $text . '</h' . $level . '>';
	return array( 'blockName' => 'core/heading', 'attrs' => 2 === $level ? array() : array( 'level' => $level ), 'innerBlocks' => array(), 'innerHTML' => $html, 'innerContent' => array( $html ) );
}

function zv_list( $items ) {
	$li = array_map( fn( $t ) => array( 'blockName' => 'core/list-item', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => "<li>$t</li>", 'innerContent' => array( "<li>$t</li>" ) ), $items );
	return array(
		'blockName'    => 'core/list',
		'attrs'        => array(),
		'innerBlocks'  => $li,
		'innerHTML'    => '<ul class="wp-block-list"></ul>',
		'innerContent' => array_merge( array( '<ul class="wp-block-list">' ), array_fill( 0, count( $li ), null ), array( '</ul>' ) ),
	);
}

/** A document: [ ['h', text], ['p', text], ['ul', [items]] ] → blocks. */
function zv_doc( $parts ) {
	return array_map( fn( $x ) => 'h' === $x[0] ? zv_h( $x[1] ) : ( 'ul' === $x[0] ? zv_list( $x[1] ) : zv_p( $x[1] ) ), $parts );
}

function zv_company() {
	return array( 'ul', array(
		'<strong>Company:</strong> ZenVida Foot &amp; Body S.L.',
		'<strong>Registered office:</strong> Calle Virgen de los Peligros, 12, 4th floor, 28013 Madrid, Spain',
		'<strong>Tax ID (NIF):</strong> B21653738',
		'<strong>Email:</strong> <a href="mailto:hola@zenvida.es">hola@zenvida.es</a>',
		'<strong>Website:</strong> www.zenvida.es',
	) );
}

/* ---------- pages ---------- */

function zv_seed_policy_pages() {
	$updated = array( 'p', '<em>Last updated: 1 June 2026</em>' );
	$link    = fn( $slug, $label ) => '<a href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '">' . $label . '</a>';

	$pages = array();

	$pages['reservations-policy'] = array( 'Reservations Policy', zv_doc( array(
		$updated,
		array( 'h', '1. Our services' ),
		array( 'p', 'All ZenVida treatments, add-ons, availability, durations and prices (VAT included) are shown on this website.' ),
		array( 'h', '2. Booking' ),
		array( 'p', 'Walk-ins are always welcome, but we recommend booking ahead — especially for weekends and holidays. You can book:' ),
		array( 'ul', array( 'online on this website;', 'by email, phone or WhatsApp;', 'in person at the studio.' ) ),
		array( 'h', '3. Deposits' ),
		array( 'p', 'To secure your appointment we may ask for a booking confirmation payment. Depending on the service, its length, the size of your group and demand, this can be:' ),
		array( 'ul', array( '€25 per person;', '€45 per person for services of €90 or more; or', 'the full price in advance.' ) ),
		array( 'p', 'We always tell you the amount when you book. The payment counts towards the price of your service and reserves your time and your therapist.' ),
		array( 'p', 'It is not refunded for cancellations, changes or no-shows outside the cancellation period. Any exceptions follow the applicable consumer protection laws.' ),
		array( 'h', '4. Arriving' ),
		array( 'p', 'Please arrive 15 minutes before your appointment to check in and fill in the Customer Intake Form. If you arrive late, your treatment may be shorter, and the full price of the booked service still applies.' ),
		array( 'h', '5. Changes and cancellations' ),
		array( 'p', 'We keep your time free just for you, so changes and cancellations need at least 4 hours’ notice. Appointments cancelled with less notice, or missed without notice, are charged in full, because we normally cannot offer that time to anyone else.' ),
		array( 'h', '6. Gift cards' ),
		array( 'p', 'Gift cards can be bought at reception and online. A gift card purchase can be cancelled within 14 calendar days, under the applicable legal conditions.' ),
		array( 'h', '7. In-room service (selected hotels only)' ),
		array( 'p', 'At selected partner hotels we can come to your room during our normal opening hours, subject to availability and confirmation. Please contact us before booking to check your hotel. In-room services follow this Reservations Policy, including cancellations, payments and delays; see the ' . $link( 'spa-rules-and-customer-information', 'Spa Rules' ) . ' for the in-room surcharges.' ),
	) ) );

	$pages['spa-rules-and-customer-information'] = array( 'Spa Rules & Customer Information', zv_doc( array(
		$updated,
		array( 'h', '1. Etiquette' ),
		array( 'p', 'Please switch your phone to vibrate before you come in. We gently encourage you not to use it during your treatment — enjoy a short digital detox.' ),
		array( 'h', '2. What to wear' ),
		array( 'p', 'We provide bathrobes, slippers and disposable underwear. For some services, such as Thai massage, we give you suitable clothes to change into, and for reflexology we have comfortable loose trousers if you would like them.' ),
		array( 'p', 'Full nudity is not permitted during any treatment.' ),
		array( 'h', '3. Unacceptable behaviour' ),
		array( 'p', 'We have zero tolerance for disrespectful, threatening or abusive language, sexual behaviour or harassment, discrimination, and being under the influence of drugs or alcohol. If we consider behaviour unacceptable, the service ends immediately and you will be asked to leave. See our ' . $link( 'code-of-conduct', 'Code of Conduct' ) . '.' ),
		array( 'h', '4. Your health' ),
		array( 'p', 'Before any service, please fill in the Customer Intake Form accurately. Your answers may lead your therapist to adapt the treatment. If you are unsure, please ask your doctor before booking.' ),
		array( 'p', '<strong>Pregnancy:</strong> please let us know — we offer specialised treatments. We will ask you to sign an acknowledgement form and/or show medical clearance before your treatment. We cannot give full-body massages during the first three months of pregnancy.' ),
		array( 'p', 'Sometimes a requested service cannot be performed. Your therapist will then suggest a suitable alternative; if no service can be performed safely, you are refunded in full.' ),
		array( 'p', 'ZenVida is a non-medical environment. Our massages, treatments and holistic practices are for your wellbeing only: they are not medical or paramedical and never replace medical advice or treatment.' ),
		array( 'h', '5. Valuables' ),
		array( 'p', 'Please keep your valuables safe. ZenVida is not responsible for loss of or damage to personal belongings during your visit.' ),
		array( 'h', '6. Children' ),
		array( 'p', 'Minors must be accompanied by a parent or a responsible adult.' ),
		array( 'h', '7. In-room service (selected hotels only)' ),
		array( 'p', 'In-room massages are available at selected partner hotels during our normal opening hours, subject to availability and confirmation — please contact us to check your hotel first.' ),
		array( 'ul', array(
			'A 50% surcharge applies to every in-room service, to cover travel time and running costs.',
			'If we need to bring extra equipment, such as a massage table, there is an additional €50 charge.',
			'Please be ready at the agreed time. If a delay on your side affects the appointment, the treatment may be shortened to fit the booked time.',
			'If the therapist has to wait for a long time, stay longer than agreed or outside our opening hours, an extra charge of up to 100% of the service price may apply.',
		) ),
		array( 'h', '8. Liability and risk' ),
		array( 'p', 'Treatments, events and activities can involve some inherent risk. You are responsible for giving accurate health information and for following our team’s instructions. As far as the law allows, ZenVida is not responsible for loss or damage caused by misuse of the facilities, not following instructions, or personal belongings brought to the studio.' ),
		array( 'h', '9. Right of withdrawal' ),
		array( 'p', 'Under Article 103 of the Spanish consumer law (TRLDCU), once a service has started or been completed with your prior express consent and acknowledgement, the right of withdrawal or cancellation no longer applies.' ),
	) ) );

	$pages['code-of-conduct'] = array( 'Code of Conduct', zv_doc( array(
		$updated,
		array( 'p', 'This Code of Conduct keeps ZenVida safe and enjoyable for everyone. Signs inside the studio are part of our Terms &amp; Conditions and of this Code.' ),
		array( 'p', 'If we reasonably consider that it has been breached, we may end all services immediately — including membership — without a refund, and take further action if appropriate.' ),
		array( 'h', '1. Damage to property' ),
		array( 'p', 'You are responsible for any damage that you or your guests cause to ZenVida property or equipment.' ),
		array( 'h', '2. Access and behaviour' ),
		array( 'p', 'Please be respectful at the studio and in all contact with our team. We have zero tolerance for behaviour that could cause injury or affect anyone’s safety or wellbeing. This includes any sexual behaviour or solicitation, and behaviour that is disruptive, unreasonable, disrespectful, abusive, violent, discriminatory or intimidating.' ),
		array( 'p', 'A breach may lead to removal from the studio, suspension of access or the end of a membership under the Membership Terms &amp; Conditions, without prior notice.' ),
		array( 'h', '3. No smoking' ),
		array( 'p', 'The studio is entirely non-smoking, including e-cigarettes.' ),
		array( 'h', '4. Privacy' ),
		array( 'p', 'Please do not photograph, record or film anyone without their consent.' ),
		array( 'h', '5. Phones' ),
		array( 'p', 'Out of courtesy to other guests and our team, please do not use your phone in a way that disturbs anyone’s quiet, comfort or privacy.' ),
		array( 'h', '6. Reviews and comments online' ),
		array( 'p', 'Honest feedback is always welcome — please share it fairly and respectfully. Do not publish, or encourage others to publish, content that is knowingly false, misleading, defamatory, threatening or harassing, or that is meant to harm ZenVida, our team or other guests.' ),
		array( 'p', 'If something was not right, please tell us directly so we can look into it and put it right.' ),
	) ) );

	$pages['terms-of-use'] = array( 'Website Terms of Use', zv_doc( array(
		$updated,
		array( 'h', '1. Who we are' ),
		array( 'p', 'In line with Article 10 of Spanish Law 34/2002 on Information Society Services and Electronic Commerce (LSSI-CE):' ),
		zv_company(),
		array( 'h', '2. What this website is for' ),
		array( 'p', 'This website gives information about the massage, reflexology and wellness services of ZenVida Foot &amp; Body S.L. You can use it to contact us, request appointments, buy products and services, and receive news. By using it, you accept these Terms of Use in full.' ),
		array( 'p', 'These terms also apply to every purchase of services or products from ZenVida, together with the policies published on this website (' . $link( 'reservations-policy', 'Reservations Policy' ) . ', ' . $link( 'spa-rules-and-customer-information', 'Spa Rules' ) . ', ' . $link( 'code-of-conduct', 'Code of Conduct' ) . ', Privacy and Cookies Policies). They apply for an indefinite period.' ),
		array( 'h', '3. Using the website' ),
		array( 'p', 'The website is free to use, apart from the cost of your internet connection. Please use it lawfully and appropriately, and in particular do not:' ),
		array( 'ul', array( 'use it for fraudulent or illegal purposes;', 'introduce or spread viruses or other harmful software;', 'damage, disable or interfere with how it works;', 'use it to send advertising or unsolicited messages (spam).' ) ),
		array( 'h', '4. Content and intellectual property' ),
		array( 'p', 'All texts, images, logos, designs and other content belong to ZenVida Foot &amp; Body S.L. or their rightful owners and are protected by Spanish and international intellectual and industrial property law. They may not be reproduced, distributed, modified or publicly communicated without our prior written permission.' ),
		array( 'h', '5. Information about our services' ),
		array( 'p', 'Descriptions of our treatments and our journal articles are for information only and are not medical advice, diagnosis or treatment. Effects and benefits vary from person to person. If you have any health concern or medical condition, please ask your doctor before booking.' ),
		array( 'h', '6. Limitation of liability' ),
		array( 'p', 'ZenVida Foot &amp; Body S.L. is not liable for:' ),
		array( 'ul', array( 'damage or loss caused by misuse of the website;', 'technical failures, temporary interruptions or errors in accessing it;', 'viruses or other harmful elements beyond our control;', 'how the information on the website is interpreted or used.' ) ),
		array( 'h', '7. Links to other websites' ),
		array( 'p', 'This website may link to other sites, such as social networks. We are not responsible for their content or practices.' ),
		array( 'h', '8. Personal data' ),
		array( 'p', 'Personal data sent through our forms, appointment requests or email is processed under the EU General Data Protection Regulation (GDPR, 2016/679) and Spanish Organic Law 3/2018 (LOPDGDD). See our ' . $link( 'privacy-policy', 'Privacy Policy' ) . '.' ),
		array( 'h', '9. Cookies' ),
		array( 'p', 'This website uses cookies and similar technologies. See our ' . $link( 'cookies-policy', 'Cookies Policy' ) . '.' ),
		array( 'h', '10. Changes' ),
		array( 'p', 'We may update these Terms of Use at any time. The current version, with the date of the latest update, is always on this page.' ),
		array( 'h', '11. Applicable law and courts' ),
		array( 'p', 'These terms are governed by Spanish law. Unless consumer protection law says otherwise, any dispute will be heard by the courts of Madrid, Spain.' ),
	) ) );

	$pages['privacy-policy'] = array( 'Privacy Policy', zv_doc( array(
		$updated,
		array( 'h', '1. Who is responsible for your data' ),
		zv_company(),
		array( 'h', '2. Why we use your data' ),
		array( 'ul', array(
			'To handle your appointment requests and questions, including the contact form, gift card requests and job applications on this website.',
			'To send confirmations, updates and information about your booking.',
			'To look after our relationship with our clients.',
			'If you ask for it, to send occasional news and wellness tips (newsletter).',
		) ),
		array( 'p', 'We never sell, rent or give your personal data to third parties.' ),
		array( 'h', '3. Legal basis' ),
		array( 'ul', array( 'Your consent, when you fill in a contact or booking form.', 'Performing a contract: managing your appointments and billing.', 'Legal obligations, such as keeping business records.' ) ),
		array( 'h', '4. How long we keep it' ),
		array( 'p', 'Only as long as we need it for the purpose it was collected for, or as long as the law requires — normally 5 years for business records. [To confirm: how long job applications and CVs are kept, e.g. 12 months.]' ),
		array( 'h', '5. Who we share it with' ),
		array( 'p', 'Nobody, unless the law requires it or it is needed to give you the service you asked for — for example our online booking tool, membership and loyalty card management, and our email provider.' ),
		array( 'h', '6. Your rights' ),
		array( 'p', 'You can ask to:' ),
		array( 'ul', array( 'see the personal data we hold about you;', 'correct or delete it;', 'restrict or object to how we use it;', 'receive it in a portable format.' ) ),
		array( 'p', 'Write to <a href="mailto:hola@zenvida.es">hola@zenvida.es</a> with proof of identity. You can also complain to the Spanish Data Protection Agency (AEPD) at <a href="https://www.aepd.es" target="_blank" rel="noopener">www.aepd.es</a>.' ),
		array( 'h', '7. CCTV' ),
		array( 'p', 'For safety and security, there are surveillance cameras in the common areas of the studio.' ),
		array( 'h', '8. Security' ),
		array( 'p', 'We use appropriate technical and organisational measures to protect your data against unauthorised access or misuse.' ),
	) ) );

	$pages['cookies-policy'] = array( 'Cookies Policy', zv_doc( array(
		$updated,
		array( 'h', '1. What are cookies?' ),
		array( 'p', 'Cookies are small text files that a website saves on your device. Similar technologies, such as your browser’s local storage, work in the same way. They help a website work properly, remember your choices and understand how it is used.' ),
		array( 'h', '2. What this website uses' ),
		array( 'ul', array(
			'<strong>Necessary:</strong> to keep the website working and secure — for example, signing in to the admin area (our team only).',
			'<strong>Preferences:</strong> stored in your browser, for example to remember that you closed an offer window so we do not show it again.',
			'<strong>Analytics:</strong> to understand how visitors use the website (for example Google Analytics) — only with your consent. [To confirm when analytics is installed.]',
			'<strong>Third parties:</strong> embedded content or social media features may set their own cookies. [To confirm: web fonts will be served from our own server.]',
		) ),
		array( 'h', '3. Your choices' ),
		array( 'p', 'On your first visit, a banner lets you accept, reject or choose which cookies we use. You can change your mind at any time, and you can also manage cookies in your browser settings.' ),
		array( 'h', '4. Turning cookies off' ),
		array( 'p', 'Most browsers let you block or delete cookies. Please note that some parts of the website may then not work properly.' ),
		array( 'h', '5. Questions' ),
		array( 'p', 'Write to us at <a href="mailto:hola@zenvida.es">hola@zenvida.es</a>.' ),
	) ) );

	$ids = array();
	foreach ( $pages as $slug => $p ) {
		$args = array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $p[0], 'post_name' => $slug, 'post_content' => wp_slash( serialize_blocks( $p[1] ) ) );
		// WordPress creates a draft "Privacy Policy" page on install: reuse it, so its privacy tools point here.
		$existing = 'privacy-policy' === $slug ? (int) get_option( 'wp_page_for_privacy_policy' ) : 0;
		if ( $existing && get_post( $existing ) ) {
			$args['ID'] = $existing;
			$ids[ $slug ] = wp_update_post( $args );
		} else {
			$ids[ $slug ] = wp_insert_post( $args );
		}
	}
	update_option( 'wp_page_for_privacy_policy', $ids['privacy-policy'] );
	return $ids;
}

function zv_seed_group_and_jobs_pages() {
	$wa   = 'https://wa.me/' . preg_replace( '/\D/', '', zv_opt( 'whatsapp' ) ) . '?text=' . rawurlencode( "Hi! We'd like to book a group appointment." );
	$step = fn( $t, $x ) => zv_b( 'zv/step', array( 'title' => $t, 'text' => $x ) );

	$group = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Group Appointments', 'post_name' => 'group-appointments', 'post_content' => wp_slash( serialize_blocks( array(
		zv_sec( array( 'eyebrow' => 'Groups', 'title' => 'Relax <em>together</em>', 'text' => 'A group is two or more people treated at the same time. Our reception team arranges every group appointment personally, so groups are not booked online.' ), array(
			zv_b( 'zv/callout', array( 'icon' => '', 'text' => '<strong>Phone or WhatsApp ' . esc_html( zv_opt( 'phone' ) ) . '</strong> · ' . esc_html( zv_opt( 'email' ) ), 'price' => '', 'linkLabel' => 'Message us on WhatsApp', 'linkUrl' => $wa ) ),
		) ),
		zv_sec( array( 'background' => 'cream', 'eyebrow' => 'How it works', 'title' => 'Four simple steps' ), array( zv_b( 'zv/steps', array(), array(
			$step( 'Tell us', 'How many of you, the day and time you would like, and which rituals.' ),
			$step( 'We plan it', 'We match therapists and rooms so you are treated at the same time.' ),
			$step( 'Confirm', 'A deposit per person may be needed to hold the times — we tell you the amount.' ),
			$step( 'Arrive together', 'Come 15 minutes early so everyone can fill in a short health form.' ),
		) ) ) ),
		zv_sec( array( 'eyebrow' => 'Why a group ritual?', 'title' => 'Made for celebrations — and tired feet', 'text' => 'Most of our groups are friends, families and colleagues marking something: a birthday, a hen or bachelorette party, or a pause between museums when everyone’s feet have had enough of Madrid.' ), array(
			zv_b( 'zv/split', array(), array(
				zv_b( 'zv/contact-form', array( 'topics' => 'Group appointment, Birthday, Hen / bachelorette party, Team or company, Other', 'buttonLabel' => 'Send group request' ) ),
				zv_b( 'zv/visit', array( 'eyebrow' => '', 'layout' => 'card' ) ),
			) ),
		) ),
	) ) ) ) );

	$job = fn( $t, $m, $x, $n ) => zv_b( 'zv/job', array( 'title' => $t, 'meta' => $m, 'text' => $x, 'needs' => $n ) );
	$benefit = fn( $i, $t, $x ) => zv_b( 'zv/benefit', array( 'icon' => $i, 'title' => $t, 'text' => $x ) );
	$jobs = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Work with us', 'post_name' => 'work-with-us', 'post_content' => wp_slash( serialize_blocks( array(
		zv_sec( array( 'eyebrow' => 'Work with us', 'title' => 'Join the ZenVida <em>team</em>', 'text' => 'We are always happy to meet therapists and hosts who love what they do and want to work in a calm, respectful place in the heart of Madrid.', 'linkLabel' => 'Apply now', 'linkUrl' => '#apply', 'linkStyle' => 'button' ), array(
			zv_b( 'zv/benefits', array(), array(
				$benefit( 'leaf', 'A calm place to work', 'Quiet treatment rooms, natural botanical products and time between guests to reset.' ),
				$benefit( 'award', 'Our signature rituals', 'We train you in the ZenVida rituals, from Deluxe Reflexology to Thai Andaluz.' ),
				$benefit( 'users', 'Guests from everywhere', 'Locals and travellers from around the world, seven days a week.' ),
				$benefit( 'heart', 'Respect comes first', 'Our Code of Conduct protects our team as much as our guests.' ),
			) ),
		) ),
		zv_sec( array( 'background' => 'cream', 'eyebrow' => 'Open roles', 'title' => 'Who we are looking for' ), array( zv_b( 'zv/jobs', array(), array(
			$job( 'Massage therapist', 'Full or part time · Madrid Centro', 'Swedish, deep tissue and Thai massage for locals and travellers.', "Certified massage training\nExperience with full-body massage\nSpanish and English" ),
			$job( 'Reflexologist', 'Full or part time · Madrid Centro', 'Foot reflexology and our Deluxe Reflexology ritual — our speciality.', "Reflexology training\nCalm, warm way with guests\nSpanish and English" ),
			$job( 'Reception & guest care', 'Part time · weekends', 'The first smile our guests see: bookings, WhatsApp, welcomes and gift cards.', "Spanish and English\nOrganised and friendly\nBooking software experience is a plus" ),
		) ) ) ),
		zv_sec( array( 'anchor' => 'apply', 'eyebrow' => 'Apply', 'title' => 'Send your application', 'text' => 'Tell us a little about yourself. We read every application and reply if there is a good fit.' ), array( zv_b( 'zv/job-form' ) ) ),
	) ) ) ) );

	return array( 'group' => $group, 'jobs' => $jobs );
}
