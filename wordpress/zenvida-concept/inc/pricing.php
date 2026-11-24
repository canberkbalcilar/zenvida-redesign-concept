<?php
/**
 * Admin helpers for the price list:
 *  - Duplicate: copy a ritual (or event / popup) as a draft, with meta, categories and page sections.
 *  - Change prices: raise or lower many prices at once, now or from a date, with a preview,
 *    a list of scheduled changes (cancel) and recent changes (undo).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ----------------------------------------------------------------- duplicate */

function zv_duplicate_types() {
	return array( 'ritual', 'zv_event', 'zv_popup' );
}

function zv_duplicate_link( $id ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=zv_duplicate&id=' . (int) $id ), 'zv_duplicate_' . (int) $id );
}

add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( in_array( $post->post_type, zv_duplicate_types(), true ) && current_user_can( 'edit_post', $post->ID ) ) {
		$actions['zv_duplicate'] = '<a href="' . esc_url( zv_duplicate_link( $post->ID ) ) . '">Duplicate</a>';
	}
	return $actions;
}, 10, 2 );

add_action( 'admin_post_zv_duplicate', function () {
	$id = absint( $_GET['id'] ?? 0 ); // phpcs:ignore
	check_admin_referer( 'zv_duplicate_' . $id );
	$src = get_post( $id );
	if ( ! $src || ! in_array( $src->post_type, zv_duplicate_types(), true ) || ! current_user_can( 'edit_post', $id ) || ! current_user_can( get_post_type_object( $src->post_type )->cap->create_posts ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	$new = wp_insert_post( wp_slash( array(
		'post_type'    => $src->post_type,
		'post_status'  => 'draft',
		'post_title'   => $src->post_title . ' (copy)',
		'post_content' => $src->post_content,
		'post_excerpt' => $src->post_excerpt,
		'menu_order'   => 0, // rituals: placed at the end of the list
	) ) );
	if ( ! $new || is_wp_error( $new ) ) {
		wp_die( 'The copy could not be created.' );
	}
	$skip = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date' );
	foreach ( get_post_meta( $id ) as $key => $values ) {
		if ( in_array( $key, $skip, true ) ) {
			continue;
		}
		foreach ( $values as $v ) {
			add_post_meta( $new, $key, wp_slash( maybe_unserialize( $v ) ) );
		}
	}
	if ( 'zv_popup' === $src->post_type ) { // a copied popup starts switched off
		$m           = (array) get_post_meta( $new, 'zv_popup', true );
		$m['active'] = '';
		update_post_meta( $new, 'zv_popup', $m );
	}
	foreach ( get_object_taxonomies( $src->post_type ) as $tax ) {
		wp_set_object_terms( $new, wp_get_object_terms( $id, $tax, array( 'fields' => 'ids' ) ), $tax );
	}
	wp_safe_redirect( add_query_arg( 'zv_copied', 1, get_edit_post_link( $new, 'url' ) ) );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( ! empty( $_GET['zv_copied'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success is-dismissible"><p><b>Copy created as a draft.</b> Change the name and whatever is different, then click Publish.' . ( 'zv_popup' === get_post_type( absint( $_GET['post'] ?? 0 ) ) ? ' The copy starts switched off.' : '' ) . '</p></div>';
	}
} );

// The ritual form gets a "Duplicate" link too.
add_action( 'zv_ritual_details_extra', function ( $post ) {
	if ( 'auto-draft' !== $post->post_status ) {
		echo '<p class="zv-dup"><a href="' . esc_url( zv_duplicate_link( $post->ID ) ) . '">Duplicate this ritual</a> <span class="description">to create a similar one without starting from zero.</span></p>';
	}
}, 99 );

/* ------------------------------------------------------------- change prices */

function zv_price_changes() {
	$c = get_option( 'zv_price_changes', array() );
	return is_array( $c ) ? $c : array();
}

/** New price for one old price. */
function zv_price_apply_rule( $price, $rule ) {
	if ( null === $price ) {
		return null; // "Price at studio" stays as it is
	}
	$amount = (float) $rule['amount'];
	$sign   = 'down' === $rule['dir'] ? -1 : 1;
	$new    = 'pct' === $rule['unit'] ? $price * ( 1 + $sign * $amount / 100 ) : $price + $sign * $amount;
	$new    = 'five' === $rule['round'] ? round( $new / 5 ) * 5 : round( $new );
	return max( 0, (int) $new );
}

function zv_price_rule_label( $rule, $count ) {
	$amount = rtrim( rtrim( number_format( (float) $rule['amount'], 2, '.', '' ), '0' ), '.' );
	$what   = ( 'down' === $rule['dir'] ? '−' : '+' ) . ( 'pct' === $rule['unit'] ? $amount . '%' : '€' . $amount );
	return sprintf( '%s on %d ritual%s%s', $what, $count, 1 === $count ? '' : 's', 'five' === $rule['round'] ? ', rounded to €5' : '' );
}

/** Apply a stored change now. Saves the old prices so it can be undone. */
function zv_price_run( $change_id ) {
	$all = zv_price_changes();
	if ( empty( $all[ $change_id ] ) || 'pending' !== $all[ $change_id ]['status'] ) {
		return;
	}
	$c        = $all[ $change_id ];
	$snapshot = array();
	foreach ( $c['ids'] as $rid ) {
		if ( 'ritual' !== get_post_type( $rid ) ) {
			continue;
		}
		$old = (string) get_post_meta( $rid, 'zv_options', true );
		$snapshot[ $rid ] = $old;
		$opts = zv_parse_options( $old );
		$new  = implode( ', ', array_map( fn( $o ) => $o[0] . ':' . ( null === $o[1] ? '' : zv_price_apply_rule( $o[1], $c['rule'] ) ), $opts ) );
		update_post_meta( $rid, 'zv_options', $new );
		clean_post_cache( $rid );
	}
	$all[ $change_id ]['status']   = 'done';
	$all[ $change_id ]['done_at']  = time();
	$all[ $change_id ]['snapshot'] = $snapshot;
	update_option( 'zv_price_changes', $all, false );
}
add_action( 'zv_price_change_run', 'zv_price_run' );

// If the scheduled task was missed (no visits at that moment), catch up on the next page load.
add_action( 'init', function () {
	foreach ( zv_price_changes() as $id => $c ) {
		if ( 'pending' === $c['status'] && $c['run_at'] && $c['run_at'] <= time() ) {
			zv_price_run( $id );
		}
	}
}, 30 );

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=ritual', 'Change prices', 'Change prices', 'edit_others_posts', 'zv-prices', 'zv_prices_page' );
} );

add_action( 'admin_post_zv_prices', function () {
	check_admin_referer( 'zv_prices' );
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'Not allowed.', 403 );
	}
	$in   = wp_unslash( $_POST ); // phpcs:ignore
	$back = admin_url( 'edit.php?post_type=ritual&page=zv-prices' );
	$all  = zv_price_changes();

	// Cancel a scheduled change / undo an applied one
	if ( ! empty( $in['cancel'] ) && isset( $all[ $in['cancel'] ] ) && 'pending' === $all[ $in['cancel'] ]['status'] ) {
		$all[ $in['cancel'] ]['status'] = 'cancelled';
		wp_clear_scheduled_hook( 'zv_price_change_run', array( $in['cancel'] ) );
		update_option( 'zv_price_changes', $all, false );
		wp_safe_redirect( add_query_arg( 'zv_msg', 'cancelled', $back ) );
		exit;
	}
	if ( ! empty( $in['undo'] ) && isset( $all[ $in['undo'] ] ) && 'done' === $all[ $in['undo'] ]['status'] ) {
		foreach ( (array) $all[ $in['undo'] ]['snapshot'] as $rid => $old ) {
			update_post_meta( (int) $rid, 'zv_options', $old );
			clean_post_cache( (int) $rid );
		}
		$all[ $in['undo'] ]['status'] = 'undone';
		update_option( 'zv_price_changes', $all, false );
		wp_safe_redirect( add_query_arg( 'zv_msg', 'undone', $back ) );
		exit;
	}

	// New change
	$scope = in_array( $in['scope'] ?? '', array( 'all', 'area', 'pick' ), true ) ? $in['scope'] : 'all';
	$ids   = array();
	if ( 'pick' === $scope ) {
		$ids = array_map( 'absint', (array) ( $in['ids'] ?? array() ) );
	} else {
		$args = array( 'post_type' => 'ritual', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'numberposts' => -1, 'fields' => 'ids' );
		if ( 'area' === $scope ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'area', 'field' => 'term_id', 'terms' => absint( $in['area'] ?? 0 ) ) ); // phpcs:ignore
		}
		$ids = get_posts( $args );
	}
	$ids    = array_values( array_filter( $ids, fn( $i ) => 'ritual' === get_post_type( $i ) && array_filter( zv_parse_options( get_post_meta( $i, 'zv_options', true ) ), fn( $o ) => null !== $o[1] ) ) );
	$amount = max( 0, (float) str_replace( ',', '.', (string) ( $in['amount'] ?? 0 ) ) );
	if ( ! $ids || ! $amount ) {
		wp_safe_redirect( add_query_arg( 'zv_msg', 'empty', $back ) );
		exit;
	}
	$rule = array(
		'dir'    => 'down' === ( $in['dir'] ?? '' ) ? 'down' : 'up',
		'amount' => $amount,
		'unit'   => 'eur' === ( $in['unit'] ?? '' ) ? 'eur' : 'pct',
		'round'  => 'five' === ( $in['round'] ?? '' ) ? 'five' : 'one',
	);
	$run_at = 0;
	if ( 'date' === ( $in['when'] ?? '' ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) ( $in['date'] ?? '' ) ) ) {
		$run_at = zv_popup_time( $in['date'] . 'T00:00' ); // midnight, Madrid time
		if ( $run_at <= time() ) {
			$run_at = 0;
		}
	}
	$id         = 'pc' . time() . wp_rand( 100, 999 );
	$all[ $id ] = array(
		'label'   => zv_price_rule_label( $rule, count( $ids ) ),
		'rule'    => $rule,
		'ids'     => $ids,
		'run_at'  => $run_at,
		'created' => time(),
		'user'    => wp_get_current_user()->display_name,
		'status'  => 'pending',
	);
	update_option( 'zv_price_changes', $all, false );
	if ( $run_at ) {
		wp_schedule_single_event( $run_at, 'zv_price_change_run', array( $id ) );
		wp_safe_redirect( add_query_arg( 'zv_msg', 'scheduled', $back ) );
	} else {
		zv_price_run( $id );
		wp_safe_redirect( add_query_arg( 'zv_msg', 'applied', $back ) );
	}
	exit;
} );

function zv_prices_page() {
	$rituals = get_posts( array( 'post_type' => 'ritual', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
	$data    = array();
	foreach ( $rituals as $r ) {
		$area   = get_the_terms( $r, 'area' );
		$data[] = array(
			'id'     => $r->ID,
			'name'   => html_entity_decode( get_the_title( $r ), ENT_QUOTES, 'UTF-8' ),
			'area'   => ( $area && ! is_wp_error( $area ) ) ? $area[0]->term_id : 0,
			'opts'   => zv_parse_options( get_post_meta( $r->ID, 'zv_options', true ) ),
			'status' => $r->post_status,
		);
	}
	$msgs = array(
		'applied'   => array( 'success', 'Prices updated. You can undo it below if needed.' ),
		'scheduled' => array( 'success', 'Price change scheduled. It will apply by itself on that day.' ),
		'cancelled' => array( 'success', 'Scheduled change cancelled.' ),
		'undone'    => array( 'success', 'Change undone: the previous prices are back.' ),
		'empty'     => array( 'error', 'Nothing to change: choose some rituals and an amount.' ),
	);
	$msg = sanitize_key( $_GET['zv_msg'] ?? '' ); // phpcs:ignore
	?>
	<div class="wrap zv-prices">
		<h1>Change prices</h1>
		<?php if ( isset( $msgs[ $msg ] ) ) : ?><div class="notice notice-<?php echo esc_attr( $msgs[ $msg ][0] ); ?>"><p><?php echo esc_html( $msgs[ $msg ][1] ); ?></p></div><?php endif; ?>
		<p>Raise or lower several prices at once, today or from a date. Check the preview before you confirm. "Price at studio" options are not changed.</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="zvPriceForm">
			<input type="hidden" name="action" value="zv_prices">
			<?php wp_nonce_field( 'zv_prices' ); ?>
			<div class="zv-pc-grid">
				<div class="postbox"><div class="inside">
					<h3>1. Which rituals?</h3>
					<p><label><input type="radio" name="scope" value="all" checked> All rituals</label></p>
					<p><label><input type="radio" name="scope" value="area"> One area: </label>
						<select name="area"><?php foreach ( zv_terms_ordered( 'area' ) as $t ) : ?><option value="<?php echo (int) $t->term_id; ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?></select></p>
					<p><label><input type="radio" name="scope" value="pick"> Only the ones I tick below</label></p>

					<h3>2. What change?</h3>
					<p class="zv-inline">
						<select name="dir"><option value="up">Increase</option><option value="down">Decrease</option></select>
						<label>by <input type="number" name="amount" value="5" min="0" step="0.5" style="width:80px"></label>
						<select name="unit"><option value="pct">%</option><option value="eur">€</option></select>
						<select name="round"><option value="one">Round to whole euros</option><option value="five">Round to €5</option></select>
					</p>

					<h3>3. When?</h3>
					<p><label><input type="radio" name="when" value="now" checked> Now</label></p>
					<p><label><input type="radio" name="when" value="date"> From </label><input type="date" name="date" min="<?php echo esc_attr( wp_date( 'Y-m-d', time() + DAY_IN_SECONDS ) ); ?>"> <span class="description">at 00:00, Madrid time</span></p>

					<p><button type="submit" class="button button-primary button-hero" id="zvPriceGo">Apply now</button></p>
				</div></div>

				<div class="postbox"><div class="inside">
					<h3>Preview <span class="description" id="zvPriceCount"></span></h3>
					<table class="widefat striped zv-pc-table">
						<thead><tr><th class="check-column"></th><th>Ritual</th><th>Now</th><th>New</th></tr></thead>
						<tbody id="zvPriceRows"></tbody>
					</table>
				</div></div>
			</div>
		</form>

		<?php
		$all     = zv_price_changes();
		$pending = array_filter( $all, fn( $c ) => 'pending' === $c['status'] );
		$done    = array_slice( array_reverse( array_filter( $all, fn( $c ) => 'done' === $c['status'] ), true ), 0, 5, true );
		?>
		<h2>Scheduled changes</h2>
		<?php if ( $pending ) : ?>
			<table class="widefat striped"><tbody>
			<?php foreach ( $pending as $id => $c ) : ?>
				<tr><td><b><?php echo esc_html( $c['label'] ); ?></b></td><td>From <?php echo esc_html( wp_date( 'j F Y', $c['run_at'] ) ); ?></td><td>by <?php echo esc_html( $c['user'] ); ?></td>
					<td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="zv_prices"><input type="hidden" name="cancel" value="<?php echo esc_attr( $id ); ?>"><?php wp_nonce_field( 'zv_prices' ); ?><button class="button-link zv-remove">Cancel</button></form></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php else : ?><p class="description">None.</p><?php endif; ?>

		<h2>Recent changes</h2>
		<?php if ( $done ) : ?>
			<table class="widefat striped"><tbody>
			<?php foreach ( $done as $id => $c ) : ?>
				<tr><td><b><?php echo esc_html( $c['label'] ); ?></b></td><td><?php echo esc_html( wp_date( 'j F Y, H:i', $c['done_at'] ) ); ?></td><td>by <?php echo esc_html( $c['user'] ); ?></td>
					<td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Put back the prices from before this change?');"><input type="hidden" name="action" value="zv_prices"><input type="hidden" name="undo" value="<?php echo esc_attr( $id ); ?>"><?php wp_nonce_field( 'zv_prices' ); ?><button class="button">Undo</button></form></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php else : ?><p class="description">None yet.</p><?php endif; ?>
	</div>
	<script>
	(() => {
		const R = <?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ); ?>;
		const f = document.getElementById('zvPriceForm'), rows = document.getElementById('zvPriceRows');
		const v = n => (f.querySelector(`[name="${n}"]:checked`) || f.querySelector(`[name="${n}"]`)).value;
		const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
		const apply = p => {
			if (p == null) return null;
			const a = parseFloat(String(f.amount.value).replace(',', '.')) || 0, s = v('dir') === 'down' ? -1 : 1;
			let n = v('unit') === 'pct' ? p * (1 + s * a / 100) : p + s * a;
			n = v('round') === 'five' ? Math.round(n / 5) * 5 : Math.round(n);
			return Math.max(0, n);
		};
		const fmt = o => o.map(x => `${x[0]} min ${x[1] == null ? 'at studio' : '€' + x[1]}`).join(' · ');
		const priced = r => r.opts.some(o => o[1] != null);
		rows.innerHTML = R.map(r => `<tr data-id="${r.id}"><th class="check-column"><input type="checkbox" name="ids[]" value="${r.id}" aria-label="${esc(r.name)}"></th>
			<td>${esc(r.name)}${r.status !== 'publish' ? ' <span class="description">(' + esc(r.status) + ')</span>' : ''}</td><td>${fmt(r.opts)}</td><td class="zv-pc-new"></td></tr>`).join('');
		const trs = [...rows.children];
		function draw() {
			const scope = v('scope'), area = +f.area.value;
			let n = 0;
			R.forEach((r, i) => {
				const tr = trs[i], box = tr.querySelector('input');
				box.disabled = scope !== 'pick';
				const on = priced(r) && (scope === 'all' || (scope === 'area' && r.area === area) || (scope === 'pick' && box.checked));
				if (on) n++;
				tr.classList.toggle('zv-pc-off', !on);
				tr.querySelector('.zv-pc-new').innerHTML = on ? '<b>' + fmt(r.opts.map(o => [o[0], apply(o[1])])) + '</b>' : '<span class="description">no change</span>';
			});
			document.getElementById('zvPriceCount').textContent = `${n} ritual${n === 1 ? '' : 's'} will change`;
			const date = f.date.value, go = document.getElementById('zvPriceGo');
			go.textContent = v('when') === 'date' ? (date ? 'Schedule from ' + new Date(date + 'T00:00').toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) : 'Choose a date') : 'Apply now';
			go.disabled = !n || (v('when') === 'date' && !date);
		}
		f.addEventListener('input', draw);
		f.addEventListener('change', e => {
			if (e.target.name === 'date' && e.target.value) f.querySelector('[name=when][value=date]').checked = true;
			if (e.target.name === 'area') f.querySelector('[name=scope][value=area]').checked = true;
			draw();
		});
		f.addEventListener('submit', e => {
			const msg = v('when') === 'date' ? 'Schedule this price change?' : 'Change these prices now? You can undo it afterwards.';
			if (!confirm(msg)) e.preventDefault();
		});
		draw();
	})();
	</script>
	<?php
}
