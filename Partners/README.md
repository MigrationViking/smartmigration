# Partner files


## Naming

Each partner is three files sharing one descriptive stem:

```
harborview-it.xml     partner data
harborview-it.svg     logo
harborview-it.html    formatted presentation
```


## Partner template

The files `template.xml`, `template.html`, and `template.svg` are an empty starter
set intended to fill out and return together:

```
template.xml     partner information and contact data
template.html    plain HTML presentation text
template.svg     logo placeholder
```

The partner should copy and rename all three files with the same descriptive stem,
for example:

```
example-consulting.xml
example-consulting.html
example-consulting.svg
```

They should fill the empty elements in the XML, replace the logo SVG, and replace
the placeholder text in the HTML. The XML must keep the `logoFile` and
`descriptionFile` values aligned with the returned filenames. The three completed
files can then be placed in this folder and included in the next app build.

The template XML is intentionally not a partner until its `name` is filled in;
the loader skips it while it remains empty.

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

## Showcase PDFs

`partner-showcase.pdf` and `partner-showcase-dark.pdf` show how the partner HTML
presentations render in light and dark themes. The PDF previews use a compact
presentation area similar to the app detail panel rather than displaying the full
HTML page height.
