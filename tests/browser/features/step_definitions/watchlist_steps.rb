When /^I select the watchlist icon$/ do
  on(HomePage).watch_link_element.when_present.click
end

Then /^I receive notification that I need to log in to use the watchlist functionality$/ do
  on(HomePage).text.should include "Please login or sign up to watch this page."
end

Then /^When I click Sign In I go to the Log In page$/ do
  on(LoginPage) do |page|
    page.login_wl_element.when_present.click
    page.text.should include "Sign in"
  end
end

Then /^I receive notification that the article has been added to the watchlist$/ do
  on(HomePage) do |page|
    page.watch_note_element.exists?
  end
end

Then /^the article watchlist icon is selected$/ do
  on(HomePage) do |page|
    page.watched_link_element.should be_true
  end
end

Then /^I receive notification that the article has been removed from the watchlist$/ do
  on(HomePage) do |page|
    page.watch_note_removed_element.exists?
  end
end
Then /^the article no longer has the watchlist icon selected$/ do
  on(HomePage) do |page|
    page.watch_link_element.should be_true
  end
end

When /^I click Login$/ do
  on(HomePage).login_watchlist_element.when_present.click
end

Then /^Login page opens$/ do
  Watir::Wait.until { @browser.url.match(Regexp.escape("Special:UserLogin")) }
  @browser.url.should match Regexp.escape("Special:UserLogin")
  @browser.url.should_not match Regexp.escape("&type=signup")
end

When /^I click Sign up$/ do
  on(HomePage).sign_up_watchlist_element.when_present.click
end

Then /^Sign up page opens$/ do
  Watir::Wait.until { @browser.url.match(Regexp.escape("Special:UserLogin")) }
  @browser.url.should match Regexp.escape("Special:UserLogin")
  @browser.url.should match Regexp.escape("&type=signup")
end
