<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row footer-top gy-4">
            <!-- Branding -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="footer-brand">
                    <a href="#" class="logo-link" style="color: #FFF;">
                        <img src="{{ asset('assets/images/logo/logo1.png') }}" alt="SimPhix Logo" class="logo-img">
                    </a>
                    <p class="footer-desc">Empowering Businesses Through Smart Connectivity. Professional telecom SIM card selling and bulk agent distribution system.</p>
                    <div class="footer-socials">
                        <a href="#" class="social-link" aria-label="Facebook">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/></svg>
                        </a>
                        <a href="#" class="social-link" aria-label="Twitter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                        </a>
                        <a href="#" class="social-link" aria-label="LinkedIn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.779-1.75-1.75s.784-1.75 1.75-1.75 1.75.779 1.75 1.75-.784 1.75-1.75 1.75zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
    
            <!-- Page Links -->
            <div class="col-6 col-md-3 col-lg-2">
                <h4 class="footer-title">Navigation</h4>
                <ul class="footer-links">
                    <li><a href="#features" class="footer-link">Core Features</a></li>
                    <li><a href="#services" class="footer-link">Roles Breakdown</a></li>
                    <li><a href="#pricing" class="footer-link">SIM Pricing Plan</a></li>
                    <li><a href="#calculator" class="footer-link">Margin Calculator</a></li>
                </ul>
            </div>
    
            <!-- Quick Actions -->
            <div class="col-6 col-md-3 col-lg-2">
                <h4 class="footer-title">Account Tools</h4>
                <ul class="footer-links">
                    <li><a href="{{ route('login') }}" class="footer-link">Login to Portal</a></li>
                    @if (Route::has('register'))
                        <li><a href="{{ route('register') }}" class="footer-link">Register as Agent</a></li>
                    @endif
                    <li><a href="#support" class="footer-link">Partner Application</a></li>
                </ul>
            </div>
    
            <!-- Newsletter -->
            <div class="col-12 col-md-6 col-lg-4">
                <h4 class="footer-title">Newsletter</h4>
                <p class="footer-newsletter-desc">Subscribe to receive allocation alerts, pricing discounts, and telecom news updates.</p>
                <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Subscribed successfully!'); this.reset();">
                    <input type="email" class="newsletter-input" placeholder="Your email address" required>
                    <button type="submit" class="newsletter-btn" aria-label="Subscribe">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-copy">
                &copy; {{ date('Y') }} SimPhix. All rights reserved.
            </div>
            <div class="footer-bottom-links">
                <a href="#" class="footer-bottom-link">Privacy Policy</a>
                <a href="#" class="footer-bottom-link">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>
