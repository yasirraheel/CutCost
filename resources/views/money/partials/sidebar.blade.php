{{-- Dashboard sidebar, markup copied from the captured dashboard pages.
     Active states come from $activeNav / $activeGroup. The Connected Bank
     Account link points at the real /en/my-bank-account route, not
     #offline. The Identity entry never takes "active" on the documents
     page, matching the raw capture. --}}
<ul class="navi_dash">
                            <li class="navi_dash-item ">
                                <a class="navi_dash-link{{ $activeNav === 'dashboard' ? ' active' : '' }}" href="/en/dashboard">
        <span class="navi_dash-icon">
            
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21 13C21 18.5229 16.5228 23 11 23C5.47715 23 1 18.5229 1 13C1 7.47716 5.47715 3 11 3V13H21Z" fill="currentColor"/>
<path d="M23 11C23 11 18.5228 11 13 11V1C18.5228 1 23 5.47715 23 11Z" fill="currentColor"/>
</svg>

        </span>
        <span class="navi_dash-text">
            Dashboard        </span>
                        </a>
                            </li>
                            <li class="navi_dash-item ">
                                <a class="navi_dash-link{{ $activeGroup === 'subscriptions' ? ' active' : '' }} navi_dash-link-sub" href="#">
        <span class="navi_dash-icon">
            
<svg xmlns="http://www.w3.org/2000/svg" 
     width="24" height="24" viewBox="0 0 24 24" fill="currentColor" class="">
  <g>
    <path d="M10,2H2C1.4,2,1,2.4,1,3v8c0,0.6,0.4,1,1,1h8c0.6,0,1-0.4,1-1V3C11,2.4,10.6,2,10,2z"/>
    <path d="M10,14H2c-0.6,0-1,0.4-1,1v8c0,0.6,0.4,1,1,1h8c0.6,0,1-0.4,1-1v-8C11,14.4,10.6,14,10,14z"/>
    <path d="M22,2h-8c-0.6,0-1,0.4-1,1v8c0,0.6,0.4,1,1,1h8c0.6,0,1-0.4,1-1V3C23,2.4,22.6,2,22,2z"/>
    <path d="M22,14h-8c-0.6,0-1,0.4-1,1v8c0,0.6,0.4,1,1,1h8c0.6,0,1-0.4,1-1v-8C23,14.4,22.6,14,22,14z"/>
  </g>
</svg>
        </span>
        <span class="navi_dash-text">
            Subscriptions        </span>
                            <span class="navi_dash-hamburger">
                
<svg xmlns="http://www.w3.org/2000/svg" class="" viewBox="0 0 30 30" width="30" height="30" fill="none"><path d="M 3 7 A 1.0001 1.0001 0 1 0 3 9 L 27 9 A 1.0001 1.0001 0 1 0 27 7 L 3 7 z M 3 14 A 1.0001 1.0001 0 1 0 3 16 L 27 16 A 1.0001 1.0001 0 1 0 27 14 L 3 14 z M 3 21 A 1.0001 1.0001 0 1 0 3 23 L 27 23 A 1.0001 1.0001 0 1 0 27 21 L 3 21 z" fill="currentColor"/></svg>

            </span>
                </a>
                                <ul class="navi_dash-sub-items" style="display: {{ $activeGroup === 'subscriptions' ? 'block' : 'none' }};">
                                    <li class="navi_dash-item green">
                                        <a class="navi_dash-link{{ $activeNav === 'shared-subscriptions' ? ' active' : '' }}" href="/en/my-shared-subscriptions">
                            <span class="navi_dash-icon">
                                
<svg version="1.1" id="Icons" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
	 viewBox="0 0 32 32" fill="currentColor" class="">
    <g>
        <path d="M14,3H4C3.4,3,3,3.4,3,4v10c0,0.6,0.4,1,1,1h10c0.6,0,1-0.4,1-1V4C15,3.4,14.6,3,14,3z"/>
        <path d="M14,17H4c-0.6,0-1,0.4-1,1v10c0,0.6,0.4,1,1,1h10c0.6,0,1-0.4,1-1V18C15,17.4,14.6,17,14,17z"/>
        <path d="M28,3H18c-0.6,0-1,0.4-1,1v10c0,0.6,0.4,1,1,1h10c0.6,0,1-0.4,1-1V4C29,3.4,28.6,3,28,3z"/>
        <path d="M27,22h-3v-3c0-0.6-0.4-1-1-1s-1,0.4-1,1v3h-3c-0.6,0-1,0.4-1,1s0.4,1,1,1h3v3c0,0.6,0.4,1,1,1s1-0.4,1-1v-3h3
            c0.6,0,1-0.4,1-1S27.6,22,27,22z"/>
    </g>
</svg>
                            </span>
                            <span class="navi_dash-text">
                                Shared subscriptions                            </span>
                        </a>
                                    </li>
                                    <li class="navi_dash-item ">
                                        <a class="navi_dash-link{{ $activeNav === 'joined-subscriptions' ? ' active' : '' }}" href="/en/joined-subscriptions">
                            <span class="navi_dash-icon">
                                
<svg class="" width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M20.3766 6.25H16.35C15.975 6.25 15.6141 6.39063 15.3375 6.64375L10.7297 10.8625C10.725 10.8672 10.7203 10.8766 10.7156 10.8813C9.9375 11.6125 9.95156 12.7797 10.6172 13.5063C11.2125 14.1578 12.4641 14.3313 13.2469 13.6328C13.2516 13.6281 13.2609 13.6281 13.2656 13.6234L17.0109 10.1922C17.3156 9.91563 17.7937 9.93438 18.0703 10.2391C18.3516 10.5438 18.3281 11.0172 18.0234 11.2984L16.8 12.4188L23.625 17.9594C23.7609 18.0719 23.8828 18.1938 23.9953 18.3203V9.25L21.4359 6.69063C21.1594 6.40938 20.775 6.25 20.3766 6.25V6.25ZM25.5 9.25938V19.7547C25.5 20.5844 26.1703 21.2547 27 21.2547H30V9.25938H25.5ZM27.75 19.7547C27.3375 19.7547 27 19.4172 27 19.0047C27 18.5922 27.3375 18.2547 27.75 18.2547C28.1625 18.2547 28.5 18.5922 28.5 19.0047C28.5 19.4172 28.1625 19.7547 27.75 19.7547ZM0 21.25H3C3.82969 21.25 4.5 20.5797 4.5 19.75V9.25938H0V21.25ZM2.25 18.2547C2.6625 18.2547 3 18.5922 3 19.0047C3 19.4172 2.6625 19.7547 2.25 19.7547C1.8375 19.7547 1.5 19.4172 1.5 19.0047C1.5 18.5875 1.8375 18.2547 2.25 18.2547ZM22.6828 19.1266L15.6844 13.4453L14.2781 14.7344C12.8859 16.0047 10.7531 15.8828 9.51094 14.5281C8.25 13.15 8.34844 11.0172 9.71719 9.76094L13.5516 6.25H9.62344C9.225 6.25 8.84531 6.40938 8.56406 6.69063L6 9.25V19.7453H6.85781L11.1 23.5844C12.3844 24.6297 14.2734 24.4328 15.3187 23.1484L15.3281 23.1391L16.1672 23.8656C16.9125 24.475 18.0141 24.3578 18.6188 23.6125L20.0906 21.8031L20.3438 22.0094C20.9859 22.5297 21.9328 22.4359 22.4531 21.7891L22.8984 21.2406C23.4234 20.5938 23.325 19.6516 22.6828 19.1266V19.1266Z" fill="currentColor"/>
</svg>

                            </span>
                            <span class="navi_dash-text">
                                Joined subscriptions                            </span>
                        </a>
                                    </li>
                                    <li class="navi_dash-item ">
                                        <a class="navi_dash-link{{ $activeNav === 'waiting-list' ? ' active' : '' }}" href="/en/waiting-list">
                            <span class="navi_dash-icon">
                                
<svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
  <path d="M12 24C10.9 24 10 23.1 10 22H14C14 23.1 13.1 24 12 24ZM18 16V11C18 7.93 
          16.36 5.36 13.5 4.68V4C13.5 3.17 12.83 2.5 12 2.5C11.17 2.5 10.5 3.17 
          10.5 4V4.68C7.64 5.36 6 7.92 6 11V16L4 18V19H20V18L18 16Z">
  </path>
</svg>
                            </span>
                            <span class="navi_dash-text">
                                Waiting List                            </span>
                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="navi_dash-item ">
                                <a class="navi_dash-link{{ $activeNav === 'chat' ? ' active' : '' }}" href="/en/my-chat/subscriptions">
        <span class="navi_dash-icon">
            
<svg class=" " width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M22.4315 11.3763C22.4315 16.5547 17.6335 20.7526 11.7158 20.7526C10.6544 20.754 9.5975 20.6162 8.57202 20.3427C7.78977 20.7392 5.99354 21.5 2.9717 21.9956C2.70381 22.0385 2.50021 21.7599 2.60602 21.5107C3.0802 20.3909 3.50883 18.8988 3.63742 17.5378C1.99657 15.8903 1 13.7338 1 11.3763C1 6.1979 5.79798 2 11.7158 2C17.6335 2 22.4315 6.1979 22.4315 11.3763ZM7.69735 11.3763C7.69735 11.7315 7.55623 12.0722 7.30503 12.3234C7.05383 12.5746 6.71313 12.7158 6.35788 12.7158C6.00263 12.7158 5.66193 12.5746 5.41073 12.3234C5.15953 12.0722 5.01841 11.7315 5.01841 11.3763C5.01841 11.021 5.15953 10.6803 5.41073 10.4291C5.66193 10.1779 6.00263 10.0368 6.35788 10.0368C6.71313 10.0368 7.05383 10.1779 7.30503 10.4291C7.55623 10.6803 7.69735 11.021 7.69735 11.3763ZM13.0552 11.3763C13.0552 11.7315 12.9141 12.0722 12.6629 12.3234C12.4117 12.5746 12.071 12.7158 11.7158 12.7158C11.3605 12.7158 11.0198 12.5746 10.7686 12.3234C10.5174 12.0722 10.3763 11.7315 10.3763 11.3763C10.3763 11.021 10.5174 10.6803 10.7686 10.4291C11.0198 10.1779 11.3605 10.0368 11.7158 10.0368C12.071 10.0368 12.4117 10.1779 12.6629 10.4291C12.9141 10.6803 13.0552 11.021 13.0552 11.3763ZM17.0736 12.7158C17.4289 12.7158 17.7696 12.5746 18.0208 12.3234C18.272 12.0722 18.4131 11.7315 18.4131 11.3763C18.4131 11.021 18.272 10.6803 18.0208 10.4291C17.7696 10.1779 17.4289 10.0368 17.0736 10.0368C16.7184 10.0368 16.3777 10.1779 16.1265 10.4291C15.8753 10.6803 15.7342 11.021 15.7342 11.3763C15.7342 11.7315 15.8753 12.0722 16.1265 12.3234C16.3777 12.5746 16.7184 12.7158 17.0736 12.7158Z" fill="currentColor"/>
</svg>

        </span>
        <span class="navi_dash-text">
            Chat        </span>
                        </a>
                            </li>
                            <li class="navi_dash-item ">
                                <a class="navi_dash-link{{ $activeGroup === 'payments' ? ' active' : '' }} navi_dash-link-sub" href="#">
        <span class="navi_dash-icon">
            
<svg class="" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21.75 3.75H2.25C1.83516 3.75 1.5 4.08516 1.5 4.5V8.25H22.5V4.5C22.5 4.08516 22.1648 3.75 21.75 3.75ZM1.5 19.5C1.5 19.9148 1.83516 20.25 2.25 20.25H21.75C22.1648 20.25 22.5 19.9148 22.5 19.5V10.3125H1.5V19.5ZM15.0703 15.1875C15.0703 15.0844 15.1547 15 15.2578 15H19.125C19.2281 15 19.3125 15.0844 19.3125 15.1875V16.875C19.3125 16.9781 19.2281 17.0625 19.125 17.0625H15.2578C15.1547 17.0625 15.0703 16.9781 15.0703 16.875V15.1875Z" fill="currentColor"/>
</svg>

        </span>
        <span class="navi_dash-text">
            Payments        </span>
                            <span class="navi_dash-hamburger">
                
<svg xmlns="http://www.w3.org/2000/svg" class="" viewBox="0 0 30 30" width="30" height="30" fill="none"><path d="M 3 7 A 1.0001 1.0001 0 1 0 3 9 L 27 9 A 1.0001 1.0001 0 1 0 27 7 L 3 7 z M 3 14 A 1.0001 1.0001 0 1 0 3 16 L 27 16 A 1.0001 1.0001 0 1 0 27 14 L 3 14 z M 3 21 A 1.0001 1.0001 0 1 0 3 23 L 27 23 A 1.0001 1.0001 0 1 0 27 21 L 3 21 z" fill="currentColor"/></svg>

            </span>
                </a>
                                <ul class="navi_dash-sub-items" style="display: {{ $activeGroup === 'payments' ? 'block' : 'none' }};">
                                    <li class="navi_dash-item ">
                                        <a class="navi_dash-link{{ $activeNav === 'wallet' ? ' active' : '' }}" href="/en/my-wallet">
                            <span class="navi_dash-icon">
                                
<svg class="" width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M25.6629 8.57143H6.51786C6.07388 8.57143 5.71429 8.21183 5.71429 7.76786C5.71429 7.32388 6.07388 6.96429 6.51786 6.96429H25.8036C26.2475 6.96429 26.6071 6.60469 26.6071 6.16071C26.6071 4.8293 25.5278 3.75 24.1964 3.75H5.71429C3.9389 3.75 2.5 5.1889 2.5 6.96429V23.0357C2.5 24.8111 3.9389 26.25 5.71429 26.25H25.6629C27.0702 26.25 28.2143 25.1687 28.2143 23.8393V10.9821C28.2143 9.65273 27.0702 8.57143 25.6629 8.57143ZM23.3929 19.0179C22.5054 19.0179 21.7857 18.2982 21.7857 17.4107C21.7857 16.5233 22.5054 15.8036 23.3929 15.8036C24.2803 15.8036 25 16.5233 25 17.4107C25 18.2982 24.2803 19.0179 23.3929 19.0179Z" fill="currentColor"/>
</svg>

                            </span>
                            <span class="navi_dash-text">
                                Wallet                            </span>
                        </a>
                                    </li>
                                    <li class="navi_dash-item ">
                                        <a class="navi_dash-link{{ $activeNav === 'cards' ? ' active' : '' }}" href="/en/my-cards">
                            <span class="navi_dash-icon">
                                
<svg class="" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21.75 3.75H2.25C1.83516 3.75 1.5 4.08516 1.5 4.5V8.25H22.5V4.5C22.5 4.08516 22.1648 3.75 21.75 3.75ZM1.5 19.5C1.5 19.9148 1.83516 20.25 2.25 20.25H21.75C22.1648 20.25 22.5 19.9148 22.5 19.5V10.3125H1.5V19.5ZM15.0703 15.1875C15.0703 15.0844 15.1547 15 15.2578 15H19.125C19.2281 15 19.3125 15.0844 19.3125 15.1875V16.875C19.3125 16.9781 19.2281 17.0625 19.125 17.0625H15.2578C15.1547 17.0625 15.0703 16.9781 15.0703 16.875V15.1875Z" fill="currentColor"/>
</svg>

                            </span>
                            <span class="navi_dash-text">
                                Means of payment                            </span>
                        </a>
                                    </li>
                                    <li class="navi_dash-item ">
                                        <a class="navi_dash-link{{ $activeNav === 'bank' ? ' active' : '' }}" href="/en/my-bank-account">
                            <span class="navi_dash-icon">
                                
<svg class="" width="30" height="30" viewBox="0 0 30 30" fill="none"
    xmlns="http://www.w3.org/2000/svg">
    <path
        d="M26.8171 24.3262H3.28127C2.82794 24.4727 2.5 24.8006 2.5 25.2051V26.7677C2.5 27.1722 2.82794 27.5001 3.23244 27.5001H26.7683C27.1728 27.5001 27.5008 27.1722 27.5008 26.7677V25.2051C27.5008 24.8006 27.1728 24.4727 26.8171 24.3262Z"
        fill="currentColor" />
    <path
        d="M27.049 7.43888L15.2811 2.55592C15.1014 2.48136 14.8994 2.48136 14.7197 2.55592L2.95172 7.43888C2.67823 7.55231 2.5 7.81931 2.5 8.11537V9.58026C2.5 9.98476 2.82794 10.3127 3.23244 10.3127H26.7683C27.1728 10.3127 27.5008 9.98476 27.5008 9.58026V8.11537C27.5008 7.81931 27.3225 7.55231 27.049 7.43888ZM19.395 8.11537H10.6057C10.2012 8.11537 9.87327 7.78743 9.87327 7.38292C9.87327 6.97842 10.2012 6.65048 10.6057 6.65048H19.395C19.7996 6.65048 20.1275 6.97842 20.1275 7.38292C20.1275 7.78743 19.7996 8.11537 19.395 8.11537Z"
        fill="currentColor" />
    <path
        d="M3.8916 21.543V23.0079H9.50701V21.543C9.50701 21.1385 9.17907 20.8105 8.77456 20.8105H4.62405C4.21954 20.8105 3.8916 21.1385 3.8916 21.543Z"
        fill="currentColor" />
    <path d="M4.86816 11.7769H8.53039V19.3454H4.86816V11.7769Z" fill="currentColor" />
    <path d="M21.4707 11.7769H25.1329V19.3454H21.4707V11.7769Z" fill="currentColor" />
    <path d="M13.959 11.8745H16.0424V22.2915H13.959V11.8745Z" fill="currentColor" />
    <path
        d="M20.4941 21.543V23.0079H26.1095V21.543C26.1095 21.1385 25.7816 20.8105 25.3771 20.8105H21.2266C20.8221 20.8105 20.4941 21.1385 20.4941 21.543Z"
        fill="currentColor" />
</svg>

                            </span>
                            <span class="navi_dash-text">
                                Connected Bank Account                            </span>
                        </a>
                                    </li>
                                </ul>
                            </li>
                            <li class="navi_dash-item ">
                                <a class="navi_dash-link{{ $activeNav === 'account' ? ' active' : '' }}" href="/en/my-account">
        <span class="navi_dash-icon">
            
<svg width="30" height="30" viewBox="0 0 30 30" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="">
    <path
        d="M15 5C16.3261 5 17.5979 5.52678 18.5355 6.46447C19.4732 7.40215 20 8.67392 20 10C20 11.3261 19.4732 12.5979 18.5355 13.5355C17.5979 14.4732 16.3261 15 15 15C13.6739 15 12.4021 14.4732 11.4645 13.5355C10.5268 12.5979 10 11.3261 10 10C10 8.67392 10.5268 7.40215 11.4645 6.46447C12.4021 5.52678 13.6739 5 15 5V5ZM15 17.5C20.525 17.5 25 19.7375 25 22.5V25H5V22.5C5 19.7375 9.475 17.5 15 17.5Z" />
</svg>

        </span>
        <span class="navi_dash-text">
            Identity and Verification        </span>
                        </a>
                            </li>
                            <li class="navi_dash-item ">
                                <a class="navi_dash-link{{ $activeNav === 'settings' ? ' active' : '' }}" href="/en/my-settings">
        <span class="navi_dash-icon">
            
<svg class="" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect x="3" y="2" width="6" height="6" rx="1" fill="currentColor"/>
    <mask id="path-2-inside-1_729_47129" fill="white">
    <rect x="3" y="9" width="6" height="6" rx="1"/>
    </mask>
    <rect x="3" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="2" mask="url(#path-2-inside-1_729_47129)"/>
    <mask id="path-3-inside-2_729_47129" fill="white">
    <rect x="3" y="16" width="6" height="6" rx="1"/>
    </mask>
    <rect x="3" y="16" width="6" height="6" rx="1" stroke="currentColor" stroke-width="2" mask="url(#path-3-inside-2_729_47129)"/>
    <rect x="10.9999" y="18" width="10" height="2" rx="1" fill="currentColor"/>
    <rect x="10.9999" y="11" width="10" height="2" rx="1" fill="currentColor"/>
    <rect x="10.9999" y="4" width="10" height="2" rx="1" fill="currentColor"/>
</svg>

        </span>
        <span class="navi_dash-text">
            Preferences        </span>
                        </a>
                            </li>
                        </ul>