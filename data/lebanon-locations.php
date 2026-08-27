<?php
/**
 * Lebanon administrative dataset.
 *
 * The dataset intentionally stops at district level. City/area remains a free
 * text field because local delivery areas change frequently and merchants need
 * to describe neighborhoods in their own operational terminology.
 *
 * @package ProSolutions\LebanonCommerceToolkit
 */

return array(
	'version'          => '2026-08-27',
	'district_aliases' => array(
		// Pre-release compatibility aliases from the former eight-governorate model.
		'mount-lebanon:jbeil'    => 'keserwan-jbeil:jbeil',
		'mount-lebanon:keserwan' => 'keserwan-jbeil:keserwan',
	),
	'governorates'     => array(
		'akkar'          => array(
			'name'      => array( 'en' => 'Akkar', 'ar' => 'عكار' ),
			'districts' => array(
				'akkar' => array( 'en' => 'Akkar', 'ar' => 'عكار' ),
			),
		),
		'baalbek-hermel' => array(
			'name'      => array( 'en' => 'Baalbek-Hermel', 'ar' => 'بعلبك الهرمل' ),
			'districts' => array(
				'baalbek' => array( 'en' => 'Baalbek', 'ar' => 'بعلبك' ),
				'hermel'  => array( 'en' => 'Hermel', 'ar' => 'الهرمل' ),
			),
		),
		'beirut'         => array(
			'name'      => array( 'en' => 'Beirut', 'ar' => 'بيروت' ),
			'districts' => array(
				'beirut' => array( 'en' => 'Beirut', 'ar' => 'بيروت' ),
			),
		),
		'bekaa'          => array(
			'name'      => array( 'en' => 'Bekaa', 'ar' => 'البقاع' ),
			'districts' => array(
				'rashaya'    => array( 'en' => 'Rashaya', 'ar' => 'راشيا' ),
				'west-bekaa' => array( 'en' => 'West Bekaa', 'ar' => 'البقاع الغربي' ),
				'zahle'      => array( 'en' => 'Zahle', 'ar' => 'زحلة' ),
			),
		),
		'keserwan-jbeil' => array(
			'name'      => array( 'en' => 'Keserwan-Jbeil', 'ar' => 'كسروان - جبيل' ),
			'districts' => array(
				'jbeil'    => array( 'en' => 'Jbeil', 'ar' => 'جبيل' ),
				'keserwan' => array( 'en' => 'Keserwan', 'ar' => 'كسروان' ),
			),
		),
		'mount-lebanon' => array(
			'name'      => array( 'en' => 'Mount Lebanon', 'ar' => 'جبل لبنان' ),
			'districts' => array(
				'aley'   => array( 'en' => 'Aley', 'ar' => 'عاليه' ),
				'baabda' => array( 'en' => 'Baabda', 'ar' => 'بعبدا' ),
				'chouf'  => array( 'en' => 'Chouf', 'ar' => 'الشوف' ),
				'metn'   => array( 'en' => 'Metn', 'ar' => 'المتن' ),
			),
		),
		'nabatieh'       => array(
			'name'      => array( 'en' => 'Nabatieh', 'ar' => 'النبطية' ),
			'districts' => array(
				'bint-jbeil' => array( 'en' => 'Bint Jbeil', 'ar' => 'بنت جبيل' ),
				'hasbaya'    => array( 'en' => 'Hasbaya', 'ar' => 'حاصبيا' ),
				'marjeyoun'  => array( 'en' => 'Marjeyoun', 'ar' => 'مرجعيون' ),
				'nabatieh'   => array( 'en' => 'Nabatieh', 'ar' => 'النبطية' ),
			),
		),
		'north'          => array(
			'name'      => array( 'en' => 'North Lebanon', 'ar' => 'الشمال' ),
			'districts' => array(
				'batroun'         => array( 'en' => 'Batroun', 'ar' => 'البترون' ),
				'bcharre'         => array( 'en' => 'Bcharre', 'ar' => 'بشري' ),
				'koura'           => array( 'en' => 'Koura', 'ar' => 'الكورة' ),
				'minieh-dinnieh' => array( 'en' => 'Minieh-Dinnieh', 'ar' => 'المنية الضنية' ),
				'tripoli'         => array( 'en' => 'Tripoli', 'ar' => 'طرابلس' ),
				'zgharta'         => array( 'en' => 'Zgharta', 'ar' => 'زغرتا' ),
			),
		),
		'south'          => array(
			'name'      => array( 'en' => 'South Lebanon', 'ar' => 'الجنوب' ),
			'districts' => array(
				'jezzine' => array( 'en' => 'Jezzine', 'ar' => 'جزين' ),
				'saida'   => array( 'en' => 'Saida', 'ar' => 'صيدا' ),
				'tyre'    => array( 'en' => 'Tyre', 'ar' => 'صور' ),
			),
		),
	),
);
