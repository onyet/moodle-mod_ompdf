PDF.js for mod_ompdf
====================

This directory contains the PDF.js 3.11.174 generic distribution.

Source repository:
https://github.com/mozilla/pdf.js

Source tag:
v3.11.174

License:
Apache License 2.0. See pdfjs/LICENSE.

Reproducible update instructions
--------------------------------

1. Clone the exact upstream tag:

   git clone --branch v3.11.174 --depth 1 https://github.com/mozilla/pdf.js.git
   cd pdf.js

2. Install the dependencies from the upstream lock file:

   npm ci

3. Build the generic distribution:

   npx gulp generic

4. Replace the plugin distribution files with the generated `build/` and
   `web/` directories, and copy the upstream LICENSE file to `pdfjs/LICENSE`.

5. Keep this file and `thirdpartylibs.xml` in sync with the upstream version.
   Review any local OMPDF integration changes in `web/viewer.js` before
   committing the updated distribution.
