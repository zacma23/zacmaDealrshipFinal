<?php

namespace App\Services\AI\Knowledge;

class CrmAndBuyerRequirementsKnowledge
{
    public static function getKnowledge(): string
    {
        return <<<TEXT
### 5. CRM LEADS, BUYER REQUIREMENTS, MESSAGING & REVIEWS

#### A. Inbound CRM Leads Pipeline
- **Auto-Lead Generation**: Whenever an interested buyer submits a contact inquiry on any vehicle, property, or product, the platform automatically creates a new lead in the seller's CRM Leads pipeline.
- **3 Visual Pipeline Stages**:
  1. **New**: Fresh inbound inquiry awaiting initial seller response. Highlighted with a notification badge in the top navigation.
  2. **Contacted**: Seller has phoned, emailed, or direct-messaged the buyer to arrange inspection or test drive.
  3. **Closed**: Deal successfully concluded, vehicle handed over, or deal marked lost.
- **Lead Features**:
  - Direct buyer contact info (Full name, Email, Phone number).
  - Referenced listing title, thumbnail, and price.
  - Follow-up date scheduling and seller private notes.

#### B. Buyer Requirements Board ("Buyer Needs" Tab)
- When buyers cannot find their exact vehicle, property, or machinery in existing inventory, they can post a **Buyer Requirement**.
- **Fields**: Industry type (Vehicle, Real Estate, Apartment, Product), Title summary, City, Target Budget (Max/Min in ETB), and detailed specifications.
- **Dealer Direct Connect**: Verified sellers and dealers can browse the public requirements board and click **Connect** to instantly message the buyer with matching inventory.

#### C. In-App Direct Messaging ("Messages" Tab)
- Real-time chat between buyers and sellers.
- Listing cards embedded directly in message bubbles for full transaction context.
- Quick prompt response chips ("Is this still available?", "What is your final price in ETB?", "When can I come inspect it?").
- Unread count indicators.

#### D. Verified Reviews & Ratings
- Verified 1–5 star buyer ratings with written feedback.
- Displayed on listing detail modals and public seller profiles (`/profile/{username}`) to establish trust and reputation.
TEXT;
    }
}
