<?php
require __DIR__ . '/../src/bootstrap.php';

// Questions follow the 2004 FAQ; answers are paraphrased to describe how this recreation behaves.
$faq = [
    'what' => ['What is Thefacebook?', 'Thefacebook is an online directory that connects people through social networks at colleges and universities.'],
    'info' => ['How do you get our information? Does the school give it to you?', 'No. Your school does not give us anything about you. Everything on the site, pictures included, was put there by the people themselves.'],
    'privacy' => ['How can I protect my privacy?', 'Use the <a href="privacy-settings.php">privacy settings</a> page to choose who can see your profile: everyone at your school, or only people you have confirmed as friends.'],
    'colleges' => ['Can people from other colleges join the site?', 'They can sign up, but nobody from another school can see your profile or social network unless you have added them as a friend. Each school has its own corner of the site.'],
    'socialnet' => ['What is the social net?', 'Your <a href="socialnet.php">social net</a> is everyone whose privacy settings let you see their profile. Each time you click "social net", ten of them are picked at random.'],
    'repeat' => ['Why does the social net repeat people?', 'Because the picks are random, the same person can turn up on two pages in a row. This happens less as more people join.'],
    'search' => ['How do I search for something besides names?', 'Press the "Search all Fields" button on the <a href="search.php">search</a> page, or use <a href="search.php?mode=advanced">advanced search</a>.'],
    'reject' => ['If I reject someone, will they find out?', 'No. Their request simply disappears from your list and they are not told. To them it looks as if you just have not confirmed it yet.'],
    'viz' => ['How can I view the visualization?', 'In 2004 you needed a browser plugin. In this recreation the <a href="socialnet.php">social net</a> page draws a simple picture of your connections right in the browser.'],
    'slow' => ['Why is the visualization slow?', 'For every person on the graph we have to check whether you are allowed to see them, and normal page requests get priority over drawing pictures.'],
    'navigate' => ['How do I navigate through the visualization?', 'Click any face in the picture to go to that person\'s profile.'],
    'change' => ['Can I change my name and password?', 'Yes. You can change both on your <a href="account.php">my account</a> page. Password changes take effect immediately.'],
    'poke' => ['What is poking?', 'Honestly, we are not sure either. It is a feature with no particular purpose. Poke people and see what happens.'],
    'who' => ['Who made this site?', 'See the <a href="about.php">about</a> page.'],
    'when' => ['When was the site started?', 'Thefacebook launched to the public on Wednesday, February 4th, 2004.'],
    'class' => ['Is this a class project?', 'Nope, just for fun.'],
    'graph' => ['What kind of graph theory algorithms are you using to process connections?', 'Let\'s pretend you didn\'t ask that.'],
    'ask' => ['I have a question that\'s not covered in the FAQ. How can I ask it?', '<a href="contact.php">Email us.</a>'],
];

page_start('Frequently Asked Questions', 'FAQ');
?>
<h1 class="page-heading">[ FAQ ]</h1>
<ul class="bullets faq-index">
  <?php foreach ($faq as $id => [$q]): ?>
    <li><a href="#<?= e($id) ?>"><?= e($q) ?></a></li>
  <?php endforeach; ?>
</ul>
<div class="narrow">
<?php foreach ($faq as $id => [$q, $a]): ?>
  <?php box_start($q, 'faq-item', $id); ?>
    <p><?= $a /* trusted static HTML defined above */ ?></p>
  <?php box_end(); ?>
<?php endforeach; ?>
</div>
<?php
page_end();
