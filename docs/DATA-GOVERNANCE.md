# Location Data Governance

## Scope

The bundled dataset intentionally stops at Lebanon's first two subnational administrative levels:

- 9 governorates (`ADM1` / mohafaza)
- 26 districts (`ADM2` / qadaa/kaza)

City, village, neighborhood, street, and landmark information remains merchant-entered through WooCommerce's City / Area and address fields. This prevents the plugin from presenting a static neighborhood list as complete or operationally authoritative.

## Reference sources

The administrative structure and count were checked against current public-sector and intergovernmental references:

1. **OCHA/HDX — Lebanon Subnational Administrative Boundaries**  
   https://data.humdata.org/dataset/cod-ab-lbn
2. **OCHA/HDX — Lebanon Edge-matched Administrative Boundaries**  
   https://data.humdata.org/dataset/cod-em-lbn
3. **Lebanese Ministry of Public Health — 4Ws 2023 Annual Report**, which reports across nine governorates and identifies Keserwan-Jbeil separately.  
   https://www.moph.gov.lb/userfiles/files/4Ws%202023%20Annual%20Report.pdf
4. **UNHCR microdata documentation**, which describes Lebanon's 26 districts.  
   https://microdata.unhcr.org/index.php/catalog/286

The plugin contains names and stable slugs only; it does not redistribute geospatial boundary files.

## Stable identifiers

District values use:

```text
<governorate-slug>:<district-slug>
```

Examples:

```text
mount-lebanon:metn
keserwan-jbeil:keserwan
```

Localized labels can change without changing the stored identifier. Never rename a stable identifier in a patch release. A rename requires a migration map and backward-compatibility tests for customer/order metadata and shipping rules.

## Update procedure

For every dataset change:

1. Open an issue labeled `location-data`.
2. Link a reliable government, UN, or recognized humanitarian data source.
3. Describe whether the change affects only labels or stored identifiers.
4. Add/adjust English and Arabic tests.
5. Add a migration when an identifier must change.
6. Update the dataset `version` value and changelog.
7. Test existing saved addresses and rate tables on a staging copy.

## Community corrections

Spelling and transliteration can legitimately vary. Reports should include:

- current label;
- proposed English and Arabic labels;
- supporting source;
- whether the stable slug should remain unchanged.

The default policy is to preserve stable slugs and improve display labels only.

## Compatibility aliases

The public beta accepts the pre-release keys `mount-lebanon:jbeil` and `mount-lebanon:keserwan` and normalizes them to the current `keserwan-jbeil:*` identifiers. New records and shipping settings should always use canonical keys.
