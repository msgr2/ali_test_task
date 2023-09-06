# Features development

We describe which use cases, versions and general ideas we have to develop.

- [Use cases](#use-cases)
- [Versions](#versions)
    - [V1: UC1](#v1-uc1)
        - [Feature list - v1.0](#feature-list---v10)

<a name="use-cases"></a>

## Use cases

- UC1: Automatic sending for multi country SMS sender
- UC2: Automatic sending for USA sender
- UC3: Reselling routes
- UC4: Sending automated email sending campaigns to our own managed servers.

## Versions

### V1: UC1

#### v0.1 - mini release

- User and company management - wip
    - Admin user
    - Customer user
- Balance management
- URL shortener module -
  using [packeger](https://github.com/Jeroen-G/laravel-packager), [laravel modeles](https://github.com/nWidart/laravel-modules)
- UI IntertiaJS / Vue release - add storybook for UI components
    - Campaigns
    - Routes
- Campaign sending
    - Campaign management
    - Auto sender
    - Campaign planner
    - SMS texts management
- Offers management
- Contacts management
    - load contact api
    - contact db structure
    - basic segemnts support - tbd
- Route management
    - Route rates
    - Route management
    - Routing plans
    - Platform routes management
    - Custom routes management
- Platform
    - ~~Tests~~
    - ~~Structure~~
    - ~~Docker - wip~~
    - ~~rest api generator - https://laraveljsonapi.io/docs/3.0/tutorial/02-models.html~~
    - ~~Logs (https://github.com/opcodesio/log-viewer)~~
    - CH support
    - Queues
    - ~~Docs~~
    - Devops - dev,staging,prod

#### Launch

Goal will be to send some SMS's see how the new functionality works.

- Create sending for one of clients
- Add routes for platform

#### v0.2 - mini release

- Routes management - Custom routes support
- Balance transactions - add deposit options, connect to invoicing
- UI - SMS dashboard, user management
- Import files flow

#### backlog

- SMS text generator
- API support
- 
