# Service Pricing and Availability

Super Admins manage platform services from **Administration > Services Pricing** (`admin.services.index`). Service rows control platform availability. Each service can have variant fields with a base price and role- or user-specific prices.

## Availability Controls

The service Active Status toggle is enforced on customer service routes and reflected in the dashboard quick-service list, customer sidebar, and relevant dashboard banners. Disabled routes redirect signed-in users to the dashboard with an availability message. The public homepage marks the SIM catalog as Coming Soon when SIM services are disabled.

The platform service controls are:

| Service | Control key | Coverage |
|---|---|---|
| SIM services | `simcard` | SIM catalog, requests, inventory, and activation pages |
| Identity verification | `verification` | BVN, NIN, NIN phone, NIN demo, and verification slips |
| Airtime | `airtime` | Airtime top-up pages and actions |
| SME Data | `smedata` | Data purchase and plan lookup pages |
| CAC | `cac` | Customer CAC registration pages |
| Travel | `travel` | Customer travel catalog, request, and tracking pages |
| POS Requests | `moniepoint_pos` | Customer POS request and tracking pages |
| Business Loans | `loans` | Customer loan application and tracking pages |

Legacy service names such as `Verification`, `Airtime`, and `SIMs` are recognized so existing service fields and prices remain attached to their current records.

## Price Sources

- SIM prices on the live `/` homepage use the `simcard` service fields and the `personal` role price for guests. Signed-in users see the price for their role. Missing or disabled variants show Coming Soon instead of a hard-coded price.
- BVN/NIN, NIN phone/demo, CAC registration, Airtime, and SME Data use their configured service fields and role/user pricing.
- Travel package prices remain managed with the travel package catalog, where each package has its own price.
- POS and Business Loan request flows currently do not charge a service fee; their central service control enables or disables access to the request flow.

Use a field's Active status to remove an individual configured variant, or the service Active Status to disable the whole service group.
