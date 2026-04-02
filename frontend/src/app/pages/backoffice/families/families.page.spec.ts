import { ComponentFixture, TestBed } from '@angular/core/testing';
import { FamiliesPage } from './families.page';

describe('FamiliesPage', () => {
  let component: FamiliesPage;
  let fixture: ComponentFixture<FamiliesPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(FamiliesPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
