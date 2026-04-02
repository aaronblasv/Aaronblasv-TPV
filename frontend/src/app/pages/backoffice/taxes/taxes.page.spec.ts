import { ComponentFixture, TestBed } from '@angular/core/testing';
import { TaxesPage } from './taxes.page';

describe('TaxesPage', () => {
  let component: TaxesPage;
  let fixture: ComponentFixture<TaxesPage>;

  beforeEach(() => {
    fixture = TestBed.createComponent(TaxesPage);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
