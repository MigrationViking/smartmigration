# Partner files

This folder is the source of the Partners tab. Vite inlines it at build time, so
adding or changing a partner means editing files here and running `npm run build`.

The folder contains the first twelve demo partners. Partners 02 and 03 specialise
in operational resilience and regulatory controls: Operational Resilience Partners
and Regulatory Control Lab.

## Naming

Each partner is three files sharing one numbered stem:

```
01 harborview-it.xml     partner data
01 harborview-it.svg     logo
01 harborview-it.html    formatted presentation
```

**The leading number sets the initial sort order of the partner table.** Renumber
the files to reorder the list. A partner whose files carry no prefix falls back to
the `<sortOrder>` element in its XML, then to the order the files were read in.

The two regulated-services examples use the additional `governance-grid` and
`assurance-matrix` layouts. These layouts are deliberately different from the
standard sections, spotlight, columns, banner, and timeline examples.

## XML

`name` is the only required element. `logoFile` and `descriptionFile` may be
omitted when the three files share a stem, which is the normal case.

## HTML

The presentation file is the partner's own layout, and each one is expected to look
different. It is sanitized before display: only presentational tags survive
(headings, paragraphs, lists, tables, `blockquote`, `div`, `span`, `hr`, `a`), and
inline `style` is limited to colour, spacing, borders, and typography. Anything that
could load a resource or escape its box — `url()`, `position`, scripts, event
handlers — is stripped. Links keep only `http:`, `https:`, `mailto:` and `tel:`.

A block that sets `background-color` should always set `color` too, so it reads
correctly in both the light and dark Nextcloud themes.
