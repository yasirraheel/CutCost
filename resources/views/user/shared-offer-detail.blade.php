{{--
    Shared-subscription detail page (Raw Pages 2, spec section 3).
    Rendered by CapturedPageController into the my-shared-subscriptions
    captured shell, so the header, sidebar, mobile sheet and footer are
    the captured ones. Class names and copy follow the real detail
    capture; no new CSS or classes. Amounts come from the offer, its
    members and its offer_payments rows only.
--}}
<div class="stats">
    @if ($status)
        <div class="alert alert-success mb-3" role="status">{{ $status }}</div>
    @endif
    <div class="stats-header">
        <div class="stats-left">
            <div class="stats-infos">
                <div class="stats-name mb-sm-2 mb-md-0">{{ $name }}</div>
            </div>
            <div class="subscriptions-item-states">
                <span class="pill pill-navy">{{ $visibilityLabel }}</span>
                <span class="pill pill-green">
        Created on {{ $createdOn }}    </span>
            </div>
            <div>
                Visibility: {{ $visibilityLabel }} <a href="{{ route('user.create-subscription.edit', $offer) }}" class="stats-visibility">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19.1719 2C18.4481 2 17.7244 2.27562 17.1719 2.82812L16 4L20 8L21.1719 6.82812C22.2759 5.72413 22.2759 3.93313 21.1719 2.82812C20.6194 2.27562 19.8956 2 19.1719 2ZM14.5 5.5L3 17V21H7L18.5 9.5L14.5 5.5Z" fill="currentColor"/></svg>
                    <span>Edit</span>
                </a>
            </div>
        </div>
        <div class="stats-right">
            <div class="row align-items-center">
                <div class="col-6 col-md-12 mb-2">
                    <div class="float-md-right">
                        @if(!empty($hasChatThread))
                        <a href="/en/my-chat/subscription/{{ $identifier }}" class="stats-chat">
                            <svg class=" " width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M22.4315 11.3763C22.4315 16.5547 17.6335 20.7526 11.7158 20.7526C10.6544 20.754 9.5975 20.6162 8.57202 20.3427C7.78977 20.7392 5.99354 21.5 2.9717 21.9956C2.70381 22.0385 2.50021 21.7599 2.60602 21.5107C3.0802 20.3909 3.50883 18.8988 3.63742 17.5378C1.99657 15.8903 1 13.7338 1 11.3763C1 6.1979 5.79798 2 11.7158 2C17.6335 2 22.4315 6.1979 22.4315 11.3763ZM7.69735 11.3763C7.69735 11.7315 7.55623 12.0722 7.30503 12.3234C7.05383 12.5746 6.71313 12.7158 6.35788 12.7158C6.00263 12.7158 5.66193 12.5746 5.41073 12.3234C5.15953 12.0722 5.01841 11.7315 5.01841 11.3763C5.01841 11.021 5.15953 10.6803 5.41073 10.4291C5.66193 10.1779 6.00263 10.0368 6.35788 10.0368C6.71313 10.0368 7.05383 10.1779 7.30503 10.4291C7.55623 10.6803 7.69735 11.021 7.69735 11.3763ZM13.0552 11.3763C13.0552 11.7315 12.9141 12.0722 12.6629 12.3234C12.4117 12.5746 12.071 12.7158 11.7158 12.7158C11.3605 12.7158 11.0198 12.5746 10.7686 12.3234C10.5174 12.0722 10.3763 11.7315 10.3763 11.3763C10.3763 11.021 10.5174 10.6803 10.7686 10.4291C11.0198 10.1779 11.3605 10.0368 11.7158 10.0368C12.071 10.0368 12.4117 10.1779 12.6629 10.4291C12.9141 10.6803 13.0552 11.021 13.0552 11.3763ZM17.0736 12.7158C17.4289 12.7158 17.7696 12.5746 18.0208 12.3234C18.272 12.0722 18.4131 11.7315 18.4131 11.3763C18.4131 11.021 18.272 10.6803 18.0208 10.4291C17.7696 10.1779 17.4289 10.0368 17.0736 10.0368C16.7184 10.0368 16.3777 10.1779 16.1265 10.4291C15.8753 10.6803 15.7342 11.021 15.7342 11.3763C15.7342 11.7315 15.8753 12.0722 16.1265 12.3234C16.3777 12.5746 16.7184 12.7158 17.0736 12.7158Z" fill="currentColor"/> </svg>
                            Chat
                        </a>
                        @endif
                    </div>
                </div>
                <div class="col-6 col-md-12">
                    <div class="float-md-right">
                        <a href="{{ $detailUrl }}#subscribers-tab" class="stats-copy">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.4498 3.04883L2.84984 8.39883C2.06984 8.61883 2.05984 9.71883 2.79984 9.95883L9.30984 12.0788C9.62984 12.1788 9.87984 12.4288 9.97984 12.7488L12.0998 19.2588C12.3398 19.9988 13.4398 19.9888 13.6598 19.2088L19.0098 0.608828C19.2098 -0.0511719 18.5998 -0.151172 21.4498 3.04883Z" fill="currentColor"/><path d="M21.4498 3.04883L2.84984 8.39883C2.06984 8.61883 2.05984 9.71883 2.79984 9.95883L9.30984 12.0788C9.62984 12.1788 9.87984 12.4288 9.97984 12.7488L12.0998 19.2588C12.3398 19.9988 13.4398 19.9888 13.6598 19.2088L19.0098 0.608828" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Invite loved ones                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="stats-bloks">
        <div class="stats-blok">
            <div class="stats-blok-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M12.6193 2.00294C12.3291 1.98062 12.0437 2.08622 11.8379 2.29203L2.29202 11.8379C1.90266 12.2272 1.90266 12.8585 2.29202 13.2479L10.7521 21.708C11.1415 22.0973 11.7728 22.0973 12.1621 21.708L21.708 12.1621C21.9138 11.9563 22.0194 11.6709 21.9971 11.3807L21.3928 3.52486C21.3551 3.03454 20.9655 2.64495 20.4751 2.60723L12.6193 2.00294ZM16.3922 7.60783C17.1709 8.38656 18.4335 8.38656 19.2122 7.60783C19.9909 6.8291 19.9909 5.56653 19.2122 4.7878C18.4335 4.00907 17.1709 4.00907 16.3922 4.7878C15.6134 5.56653 15.6134 6.8291 16.3922 7.60783Z" fill="white"/> <path d="M7.02909 16.6034L14.8073 8.82518L15.3635 9.38137L7.58528 17.1595L7.02909 16.6034ZM13.5035 12.8081C13.7037 12.5627 13.7763 12.2889 13.7212 11.9868C13.6705 11.6848 13.4812 11.3699 13.1533 11.0421C12.9363 10.825 12.7301 10.6695 12.5347 10.5754C12.3394 10.4814 12.161 10.4427 11.9995 10.4595C11.8401 10.4743 11.7025 10.5372 11.5868 10.6484C11.4891 10.737 11.4274 10.8394 11.4017 10.9555C11.3783 11.0739 11.3834 11.2045 11.4169 11.3474C11.4546 11.4905 11.5166 11.6456 11.6027 11.8127C11.6888 11.9799 11.7958 12.1577 11.9236 12.3464L12.399 13.0648C12.6758 13.4753 12.8896 13.8674 13.0405 14.2411C13.1936 14.617 13.2825 14.9713 13.3072 15.3038C13.3341 15.6386 13.2937 15.9527 13.1858 16.2459C13.078 16.5391 12.9007 16.8114 12.6538 17.0628C12.2586 17.4535 11.8105 17.68 11.3096 17.7423C10.8088 17.8045 10.2777 17.7048 9.71628 17.4432C9.15716 17.1839 8.5882 16.7648 8.0094 16.186C7.42834 15.605 6.99987 15.0205 6.724 14.4327C6.44813 13.8449 6.34938 13.2722 6.42773 12.7145C6.50609 12.1568 6.78494 11.6315 7.26429 11.1386L8.87182 12.7461C8.68485 12.9602 8.57766 13.1873 8.55023 13.4272C8.52281 13.6672 8.56736 13.9143 8.68389 14.1685C8.80268 14.4251 8.98529 14.6765 9.23173 14.923C9.45783 15.1491 9.67735 15.3139 9.89031 15.4175C10.1055 15.5233 10.3047 15.5706 10.4878 15.5593C10.671 15.548 10.8265 15.4806 10.9543 15.3573C11.0696 15.2376 11.1291 15.0966 11.133 14.9344C11.139 14.7702 11.0898 14.5752 10.9855 14.3494C10.8855 14.1238 10.7353 13.8581 10.5347 13.5522L9.96085 12.6745C9.483 11.9496 9.20886 11.2764 9.13841 10.655C9.06999 10.0315 9.27289 9.48498 9.74711 9.01529C10.1297 8.62817 10.5823 8.40622 11.1048 8.34944C11.6273 8.29266 12.1754 8.38899 12.7489 8.63844C13.3224 8.88788 13.8726 9.276 14.3994 9.8028C14.9375 10.3409 15.3223 10.8898 15.5539 11.4496C15.7898 12.0095 15.8706 12.546 15.7962 13.059C15.7218 13.5721 15.4934 14.0243 15.111 14.4157L13.5035 12.8081Z" fill="currentColor"/> </svg>
            </div>
            <div class="stats-blok-name">Monthly income</div>
            <div class="stats-blok-price"><span class="t3-bold">{{ $incomeInt }}</span>{{ $incomeDec }}</div>
            <div class="stats-blok-frequency">/ month</div>
        </div>
        <div class="stats-blok">
            <div class="stats-blok-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 13C21 18.5229 16.5228 23 11 23C5.47715 23 1 18.5229 1 13C1 7.47716 5.47715 3 11 3V13H21Z" fill="currentColor"/><path d="M23 11C23 11 18.5228 11 13 11V1C18.5228 1 23 5.47715 23 11Z" fill="currentColor"/></svg>
            </div>
            <div class="stats-blok-name">Revenue per subscriber</div>
            <div class="stats-blok-price"><span>{{ $perSubscriber }}</span></div>
            <div class="stats-blok-frequency">/ month</div>
        </div>
        <div class="stats-blok">
            <div class="stats-blok-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_499_23726)"><path d="M16 11C17.66 11 18.99 9.66 18.99 8C18.99 6.34 17.66 5 16 5C14.34 5 13 6.34 13 8C13 9.66 14.34 11 16 11ZM8 11C9.66 11 10.99 9.66 10.99 8C10.99 6.34 9.66 5 8 5C6.34 5 5 6.34 5 8C5 9.66 6.34 11 8 11ZM8 13C5.67 13 1 14.17 1 16.5V18C1 18.55 1.45 19 2 19H14C14.55 19 15 18.55 15 18V16.5C15 14.17 10.33 13 8 13ZM16 13C15.71 13 15.38 13.02 15.03 13.05C15.05 13.06 15.06 13.08 15.07 13.09C16.21 13.92 17 15.03 17 16.5V18C17 18.35 16.93 18.69 16.82 19H22C22.55 19 23 18.55 23 18V16.5C23 14.17 18.33 13 16 13Z" fill="currentColor"/></g><defs><clipPath id="clip0_499_23726"><rect width="24" height="24" fill="white"/></clipPath></defs></svg>
            </div>
            <div class="stats-blok-name">Number of subscribers</div>
            <div class="stats-blok-fraction">{{ $subscriberCount }}<span>/<strong>{{ $seatsTotal }}</strong></span></div>
            <div class="stats-blok-frequency">subscribers</div>
        </div>
    </div>
    <div class="tab">
        <div class="tab-header">
            <a href="#" data-tab="#identities-tab" class="active">
                <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"> <rect x="4.5" y="18" width="4" height="2" rx="1" fill="currentColor"/> <rect x="10.5" y="18" width="4" height="2" rx="1" fill="currentColor"/> <rect x="16.5" y="18" width="4" height="2" rx="1" fill="currentColor"/> <path d="M15.3 4V2.5C15.3 1.1 13.9 0 12.5 0C11.1 0 9.7 1.1 9.7 2.5V4C9.1 4 8.5 4.6 8.5 5.2V8.7C8.5 9.4 9.1 10 9.7 10H15.2C15.9 10 16.5 9.4 16.5 8.8V5.3C16.5 4.6 15.9 4 15.3 4ZM14 4H11V2.5C11 1.7 11.7 1.2 12.5 1.2C13.3 1.2 14 1.7 14 2.5V4Z" fill="currentColor"/> <circle cx="6.5" cy="15" r="2" fill="currentColor"/> <circle cx="12.5" cy="15" r="2" fill="currentColor"/> <circle cx="18.5" cy="15" r="2" fill="currentColor"/> <path fill-rule="evenodd" clip-rule="evenodd" d="M6.5 2H1.5C0.947715 2 0.5 2.44772 0.5 3V23C0.5 23.5523 0.947716 24 1.5 24H23.5C24.0523 24 24.5 23.5523 24.5 23V3C24.5 2.44772 24.0523 2 23.5 2H18.5V4.11765H22.5V21.8824H2.5V4.11765H6.5V2Z" fill="currentColor"/> </svg>
                Login details
            </a>
            <a href="#" data-tab="#subscribers-tab">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_499_23726)"><path d="M16 11C17.66 11 18.99 9.66 18.99 8C18.99 6.34 17.66 5 16 5C14.34 5 13 6.34 13 8C13 9.66 14.34 11 16 11ZM8 11C9.66 11 10.99 9.66 10.99 8C10.99 6.34 9.66 5 8 5C6.34 5 5 6.34 5 8C5 9.66 6.34 11 8 11ZM8 13C5.67 13 1 14.17 1 16.5V18C1 18.55 1.45 19 2 19H14C14.55 19 15 18.55 15 18V16.5C15 14.17 10.33 13 8 13ZM16 13C15.71 13 15.38 13.02 15.03 13.05C15.05 13.06 15.06 13.08 15.07 13.09C16.21 13.92 17 15.03 17 16.5V18C17 18.35 16.93 18.69 16.82 19H22C22.55 19 23 18.55 23 18V16.5C23 14.17 18.33 13 16 13Z" fill="currentColor"/></g><defs><clipPath id="clip0_499_23726"><rect width="24" height="24" fill="white"/></clipPath></defs></svg>
                Subscribers
            </a>
            <a href="#" data-tab="#payments-tab">
                <svg class="" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.75 3.75H2.25C1.83516 3.75 1.5 4.08516 1.5 4.5V8.25H22.5V4.5C22.5 4.08516 22.1648 3.75 21.75 3.75ZM1.5 19.5C1.5 19.9148 1.83516 20.25 2.25 20.25H21.75C22.1648 20.25 22.5 19.9148 22.5 19.5V10.3125H1.5V19.5ZM15.0703 15.1875C15.0703 15.0844 15.1547 15 15.2578 15H19.125C19.2281 15 19.3125 15.0844 19.3125 15.1875V16.875C19.3125 16.9781 19.2281 17.0625 19.125 17.0625H15.2578C15.1547 17.0625 15.0703 16.9781 15.0703 16.875V15.1875Z" fill="currentColor"/></svg>
                Payments
            </a>
            <a href="#" data-tab="#handler-tab">
                <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg"> <path d="M10.9905 2C10.5115 2 10.1021 2.33859 10.0101 2.80859L9.67609 4.52344C8.85533 4.8338 8.09641 5.2674 7.43 5.81445L5.78547 5.24805C5.33247 5.09205 4.83406 5.27936 4.59406 5.69336L3.08625 8.30664C2.84725 8.72164 2.93423 9.2485 3.29523 9.5625L4.61359 10.709C4.54503 11.1303 4.50031 11.5593 4.50031 12C4.50031 12.4407 4.54503 12.8697 4.61359 13.291L3.29523 14.4375C2.93423 14.7515 2.84725 15.2784 3.08625 15.6934L4.59406 18.3066C4.83306 18.7216 5.33247 18.9089 5.78547 18.7539L7.43 18.1875C8.09619 18.7342 8.8557 19.1663 9.67609 19.4766L10.0101 21.1914C10.1021 21.6614 10.5115 22 10.9905 22H14.0101C14.4891 22 14.8985 21.6614 14.9905 21.1914L15.3245 19.4766C16.1453 19.1662 16.9042 18.7326 17.5706 18.1855L19.2152 18.752C19.6682 18.908 20.1666 18.7216 20.4066 18.3066L21.9144 15.6914C22.1534 15.2764 22.0664 14.7515 21.7054 14.4375L20.387 13.291C20.4556 12.8697 20.5003 12.4407 20.5003 12C20.5003 11.5593 20.4556 11.1303 20.387 10.709L21.7054 9.5625C22.0664 9.2485 22.1534 8.72164 21.9144 8.30664L20.4066 5.69336C20.1676 5.27836 19.6682 5.09109 19.2152 5.24609L17.5706 5.8125C16.9044 5.26579 16.1449 4.83366 15.3245 4.52344L14.9905 2.80859C14.8985 2.33859 14.4891 2 14.0101 2H10.9905ZM12.5003 8C14.7093 8 16.5003 9.791 16.5003 12C16.5003 14.209 14.7093 16 12.5003 16C10.2913 16 8.50031 14.209 8.50031 12C8.50031 9.791 10.2913 8 12.5003 8Z" fill="currentColor"/> </svg>
                Management
            </a>
        </div>
        <div class="tab-content">
            <div class="credentials" id="identities-tab">
                <div class="credentials-description">
                    To benefit from the service, an invitation will be sent to you by email. Send your mail in the chat to receive the invitation from the owner within 24 hours.
                </div>
                <div class="credentials-wrapper">
                    <div class="credentials-wrapper-header">
                        <span class="credentials-wrapper-header-icon">
                            <svg class="" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.0002 14.1667C10.4422 14.1667 10.8661 13.9911 11.1787 13.6785C11.4912 13.366 11.6668 12.942 11.6668 12.5C11.6668 12.058 11.4912 11.6341 11.1787 11.3215C10.8661 11.0089 10.4422 10.8333 10.0002 10.8333C9.55813 10.8333 9.13421 11.0089 8.82165 11.3215C8.50909 11.6341 8.33349 12.058 8.33349 12.5C8.33349 12.942 8.50909 13.366 8.82165 13.6785C9.13421 13.9911 9.55813 14.1667 10.0002 14.1667ZM15.0002 6.66667C15.4422 6.66667 15.8661 6.84226 16.1787 7.15482C16.4912 7.46739 16.6668 7.89131 16.6668 8.33334V16.6667C16.6668 17.1087 16.4912 17.5326 16.1787 17.8452C15.8661 18.1577 15.4422 18.3333 15.0002 18.3333H5.00016C4.55813 18.3333 4.13421 18.1577 3.82165 17.8452C3.50909 17.5326 3.3335 17.1087 3.3335 16.6667V8.33334C3.3335 7.89131 3.50909 7.46739 3.82165 7.15482C4.13421 6.84226 4.55813 6.66667 5.00016 6.66667H5.8335V5C5.8335 3.89493 6.27248 2.83513 7.05388 2.05372C7.83529 1.27232 8.89509 0.833336 10.0002 0.833336C10.5473 0.833336 11.0892 0.94111 11.5947 1.1505C12.1002 1.3599 12.5595 1.66681 12.9464 2.05372C13.3333 2.44064 13.6403 2.89996 13.8497 3.40549C14.0591 3.91101 14.1668 4.45283 14.1668 5V6.66667H15.0002ZM10.0002 2.5C9.33712 2.5 8.70124 2.76339 8.23239 3.23224C7.76355 3.70108 7.50016 4.33696 7.50016 5V6.66667H12.5002V5C12.5002 4.33696 12.2368 3.70108 11.7679 3.23224C11.2991 2.76339 10.6632 2.5 10.0002 2.5Z" fill="currentColor"/></svg>
                        </span>
                        <span>End-to-end encryption</span>
                    </div>
                    <form method="post" action="{{ $detailUrl }}">
                        @csrf
                        <div class="credentials-form-items">
                            @if ($credentials !== null)
                                @foreach ($credentials as $index => $value)
                                    <div class="mb-3">
                                        <label>{{ $credentialLabels[$index] ?? 'Value' }}</label>
                                        <input type="{{ str_contains(strtolower($credentialLabels[$index] ?? ''), 'password') ? 'password' : 'text' }}" name="credentials[]" value="{{ $value }}" class="form-control" autocomplete="off">
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class="credentials-form-label">You can update below additional information:</div>
                        <div class="credentials-form-radios">
                            <div class="credentials-form-radio">
                                <input type="radio" name="secured_description_sharing_method" id="credentialSame" value="0" @checked($securedMethod === 0)>
                                <label for="credentialSame">Same info for all subscribers</label>
                            </div>
                            <div class="credentials-form-radio">
                                <input type="radio" name="secured_description_sharing_method" id="credentialDifferent" value="1" @checked($securedMethod === 1)>
                                <label for="credentialDifferent">One message per subscriber</label>
                            </div>
                        </div>
                        <div class="secured-description-wrapper" data-type="0">
                            <textarea rows="4" class="textarea-sharesub" name="individual_secured_description" placeholder="Hello! Thanks for joining my subscription. Here are the instructions for getting started with the service:">{{ $securedShared }}</textarea>
                        </div>
                        <div style="display: none;" class="secured-description-wrapper" data-type="1">
                            @foreach ($securedIndividual as $text)
                                <textarea rows="4" class="textarea-sharesub" name="shared_secured_description[]" placeholder="Hello! Thanks for joining my subscription. Here are the instructions for getting started with the service:">{{ $text }}</textarea>
                            @endforeach
                        </div>
                        <div class="credentials-form-locked">
                            <svg class="" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.0002 14.1667C10.4422 14.1667 10.8661 13.9911 11.1787 13.6785C11.4912 13.366 11.6668 12.942 11.6668 12.5C11.6668 12.058 11.4912 11.6341 11.1787 11.3215C10.8661 11.0089 10.4422 10.8333 10.0002 10.8333C9.55813 10.8333 9.13421 11.0089 8.82165 11.3215C8.50909 11.6341 8.33349 12.058 8.33349 12.5C8.33349 12.942 8.50909 13.366 8.82165 13.6785C9.13421 13.9911 9.55813 14.1667 10.0002 14.1667ZM15.0002 6.66667C15.4422 6.66667 15.8661 6.84226 16.1787 7.15482C16.4912 7.46739 16.6668 7.89131 16.6668 8.33334V16.6667C16.6668 17.1087 16.4912 17.5326 16.1787 17.8452C15.8661 18.1577 15.4422 18.3333 15.0002 18.3333H5.00016C4.55813 18.3333 4.13421 18.1577 3.82165 17.8452C3.50909 17.5326 3.3335 17.1087 3.3335 16.6667V8.33334C3.3335 7.89131 3.50909 7.46739 3.82165 7.15482C4.13421 6.84226 4.55813 6.66667 5.00016 6.66667H5.8335V5C5.8335 3.89493 6.27248 2.83513 7.05388 2.05372C7.83529 1.27232 8.89509 0.833336 10.0002 0.833336C10.5473 0.833336 11.0892 0.94111 11.5947 1.1505C12.1002 1.3599 12.5595 1.66681 12.9464 2.05372C13.3333 2.44064 13.6403 2.89996 13.8497 3.40549C14.0591 3.91101 14.1668 4.45283 14.1668 5V6.66667H15.0002ZM10.0002 2.5C9.33712 2.5 8.70124 2.76339 8.23239 3.23224C7.76355 3.70108 7.50016 4.33696 7.50016 5V6.66667H12.5002V5C12.5002 4.33696 12.2368 3.70108 11.7679 3.23224C11.2991 2.76339 10.6632 2.5 10.0002 2.5Z" fill="currentColor"/></svg> Your login credentials and this message are stored in encrypted form on a secure server. This information will be visible only to you, your subscribers, and Sharesub customer service, if necessary.
                            If you need to provide a password, make sure it is unique and not used on any of your other accounts. </div>
                        <button type="submit" class="btn btn-sharesub green">Save</button>
                    </form>
                </div>
            </div>
            <div class="subscribers d-none" id="subscribers-tab">
                <div class="subscribers-items">
                    @foreach ($currentMembers as $member)
                        <div class="subscribers-item">
                            <div class="subscribers-item-avatar avatar me online">{{ $member->initials }}</div>
                            <div class="subscribers-item-name">
                                {{ $member->username }}
                            </div>
                            <div class="subscribers-item-content">joined {{ $member->joined_at?->format('Y/m/d') }}</div>
                            <div class="subscribers-item-actions">
                                <a type="button" href="/en/my-chat/subscription/{{ $identifier }}/{{ $member->id }}/messages">
                                    <svg class=" " width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M22.4315 11.3763C22.4315 16.5547 17.6335 20.7526 11.7158 20.7526C10.6544 20.754 9.5975 20.6162 8.57202 20.3427C7.78977 20.7392 5.99354 21.5 2.9717 21.9956C2.70381 22.0385 2.50021 21.7599 2.60602 21.5107C3.0802 20.3909 3.50883 18.8988 3.63742 17.5378C1.99657 15.8903 1 13.7338 1 11.3763C1 6.1979 5.79798 2 11.7158 2C17.6335 2 22.4315 6.1979 22.4315 11.3763ZM7.69735 11.3763C7.69735 11.7315 7.55623 12.0722 7.30503 12.3234C7.05383 12.5746 6.71313 12.7158 6.35788 12.7158C6.00263 12.7158 5.66193 12.5746 5.41073 12.3234C5.15953 12.0722 5.01841 11.7315 5.01841 11.3763C5.01841 11.021 5.15953 10.6803 5.41073 10.4291C5.66193 10.1779 6.00263 10.0368 6.35788 10.0368C6.71313 10.0368 7.05383 10.1779 7.30503 10.4291C7.55623 10.6803 7.69735 11.021 7.69735 11.3763ZM13.0552 11.3763C13.0552 11.7315 12.9141 12.0722 12.6629 12.3234C12.4117 12.5746 12.071 12.7158 11.7158 12.7158C11.3605 12.7158 11.0198 12.5746 10.7686 12.3234C10.5174 12.0722 10.3763 11.7315 10.3763 11.3763C10.3763 11.021 10.5174 10.6803 10.7686 10.4291C11.0198 10.1779 11.3605 10.0368 11.7158 10.0368C12.071 10.0368 12.4117 10.1779 12.6629 10.4291C12.9141 10.6803 13.0552 11.021 13.0552 11.3763ZM17.0736 12.7158C17.4289 12.7158 17.7696 12.5746 18.0208 12.3234C18.272 12.0722 18.4131 11.7315 18.4131 11.3763C18.4131 11.021 18.272 10.6803 18.0208 10.4291C17.7696 10.1779 17.4289 10.0368 17.0736 10.0368C16.7184 10.0368 16.3777 10.1779 16.1265 10.4291C15.8753 10.6803 15.7342 11.021 15.7342 11.3763C15.7342 11.7315 15.8753 12.0722 16.1265 12.3234C16.3777 12.5746 16.7184 12.7158 17.0736 12.7158Z" fill="currentColor"/> </svg>
                                    <span>Contact</span>
                                </a>
                            </div>
                            @if ($member->invitation_email)
                                <div class="subscribers-item-details">
                                    <div class="subscribers-item-detail">
                                        <span class="subscribers-item-detail-icon">✉️</span>
                                        <span class="subscribers-item-detail-label">Invitation :</span>
                                        <span class="subscribers-item-detail-value copy-button cursor-pointer" id="email-invite-{{ $member->id }}" data-copy-target="#email-invite-{{ $member->id }}" data-toggle="tooltip" data-placement="top" title="Click to copy">{{ $member->invitation_email }}</span>
                                        @if ($member->invite_sent_at)
                                            <span class="ml-2" data-toggle="tooltip" data-placement="top" title="Sent on {{ $member->invite_sent_at->format('d/m/Y') }} to {{ $member->invite_sent_at->format('H\hi') }}">✅</span>
                                        @else
                                            <form action="{{ $detailUrl }}/mark-email-invite-sent/{{ $member->id }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn-sharesub green small ml-2 click-load">Mark as sent</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="subscribers-actions">
                </div>
                @if ($previousMembers->isNotEmpty())
                    <div class="subscribers-old">
                        <div class="subscribers-old-header">Previous subscribers</div>
                        <div>
                            @foreach ($previousMembers as $member)
                                <div class="subscribers-old-item">
                                    <div class="subscribers-old-item-avatar">{{ $member->initials }}</div>
                                    <div class="subscribers-old-item-name">
                                        {{ $member->username }}
                                    </div>
                                    <div class="subscribers-old-item-content">joined {{ $member->joined_at?->format('Y/m/d') }}</div>
                                    <div class="subscribers-old-item-action">left {{ $member->left_at?->format('Y/m/d') }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
            <div class="payments d-none" id="payments-tab">
                <div class="payments-header">
                    <div class="payments-header-name">
                        Payment history </div>
                    <div class="payments-header-date">
                        <button type="button" data-href="{{ $detailUrl }}?date={{ $prevMonth->format('Y-m-01') }}#payments-tab" onclick="window.location.href=this.dataset.href">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.4257 18.7371C14.0186 19.1103 13.386 19.0828 13.0128 18.6757L7.51284 12.6757C7.16238 12.2934 7.16238 11.7066 7.51284 11.3243L13.0128 5.32427C13.386 4.91715 14.0186 4.88965 14.4257 5.26284C14.8328 5.63603 14.8603 6.2686 14.4871 6.67572L9.60656 12L14.4871 17.3243C14.8603 17.7314 14.8328 18.364 14.4257 18.7371Z" fill="#112E45"/></svg>
                        </button>
                        <span>{{ $monthLabel }}</span>
                        <button type="button" data-href="{{ $detailUrl }}?date={{ $nextMonth->format('Y-m-01') }}#payments-tab" onclick="window.location.href=this.dataset.href">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.57428 5.26285C9.9814 4.88966 10.614 4.91716 10.9872 5.32428L16.4872 11.3243C16.8376 11.7066 16.8376 12.2934 16.4872 12.6757L10.9872 18.6757C10.614 19.0828 9.9814 19.1104 9.57428 18.7372C9.16716 18.364 9.13966 17.7314 9.51285 17.3243L14.3934 12L9.51285 6.67573C9.13966 6.26861 9.16716 5.63604 9.57428 5.26285Z" fill="#112E45"/></svg>
                        </button>
                    </div>
                    <div class="payments-header-total">TOTAL: {{ $paymentsTotal }}</div>
                </div>
                <div class="payments-items">
                    @foreach ($payments as $payment)
                        <div class="payments-item">
                            <div class="payments-item-avatar avatar me online">{{ $payment->member->initials ?? '' }}</div>
                            <div class="payments-item-info">
                                {{ $payment->member->username ?? '' }}
                            </div>
                            <div class="payments-item-description">
                                <svg class="" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 1C5.93436 1 1 5.93436 1 12C1 18.0656 5.93436 23 12 23C18.0656 23 23 18.0656 23 12C23 5.93436 18.0656 1 12 1ZM17.2314 17.6897C17.0527 17.8684 16.818 17.9584 16.5834 17.9584C16.3487 17.9584 16.1139 17.8684 15.9353 17.6897L11.3519 13.1064C11.1796 12.9351 11.0834 12.7021 11.0834 12.4584V6.5C11.0834 5.9931 11.4939 5.58339 12 5.58339C12.5061 5.58339 12.9166 5.9931 12.9166 6.5V12.0789L17.2314 16.3936C17.5898 16.7521 17.5898 17.3313 17.2314 17.6897Z" fill="#F2C94C"/></svg>
                                <strong class="blue">{{ $payment->statusLabel }}</strong> {{ $payment->paid_at?->format('Y/m/d') }}
                            </div>
                            <div class="payments-item-price ">
                                {{ $payment->amountFormatted }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="gestion d-none" id="handler-tab">
                <div class="gestion-migrate-title mb-3">Modify your subscription price plan</div>
                <div class="gestion-migrate-description">
                    <p>Want to change your offer{{ $brandName ? ' '.$brandName : '' }}? Sharesub allows you to modify your shared subscription so that your subscribers' refunds can be adjusted.</p>
                    <p>Here's how it works:</p>
                    <ul>
                        <li>Changes can be made no more than once per quarter</li>
                        <li>You cannot make the change in the last 7 days of the month</li>
                        <li>We inform your co-subscribers to allow them to leave if they do not wish to continue</li>
                        <li>The new rate applies from the following month onwards</li>
                        <li>If the new plan offers fewer spaces, excess co-subscribers will be removed automatically</li>
                    </ul>
                </div>
                @if ($templates->isNotEmpty())
                    <form method="post" action="{{ $detailUrl }}/migrate">
                        @csrf
                        <div class="mb-3">
                            <select name="template_id" class="form-control">
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}" @selected($offer->template_id === $template->id)>{{ $template->name }} - {{ $template->priceLabel }} / month</option>
                                @endforeach
                            </select>
                            @error('template_id')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-sharesub green">Save</button>
                    </form>
                @endif
                <div class="gestion-warning-title mb-3">Cancel the subscription to {{ $name }}</div>
                <div class="gestion-warning">
                    <p class="gestion-warning-description">Are you sure you want to cancel the subscription? If you do this, you will lose all your subscribers, and their payments for the current month will be refunded. The refund will be a full refund if you have been providing the service for less than one month, or a partial refund if the subscription is older. <b>Please note that this action cannot be undone!</b></p>
                </div>
                <div class="gestion-description">To permanently cancel the subscription, type “Delete” and confirm:</div>
                <form method="post" action="{{ $detailUrl }}/end">
                    @csrf
                    <div class="mb-3">
                        <input type="text" name="confirmation" class="form-control" placeholder="Delete" autocomplete="off">
                        @error('confirmation')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-sharesub red">Cancel the subscription</button>
                </form>
            </div>
        </div>
    </div>
</div>
